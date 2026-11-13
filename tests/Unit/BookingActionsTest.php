<?php

namespace Tests\Unit;

use App\Actions\CancelBooking;
use App\Actions\ReserveBooking;
use App\Contracts\BookingStore;
use App\Contracts\ReservationRepository;
use App\Exceptions\BookingConflict;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Policies\ReservationPolicy;
use Carbon\CarbonImmutable;
use Tests\Support\InspectingServiceLock;
use Tests\TestCase;

class BookingActionsTest extends TestCase
{
    private function service(): Service
    {
        return new Service(['id' => 1, 'capacity' => 1, 'starts_at' => now()->subHour(), 'ends_at' => now()->addMinutes(5)]);
    }

    public function test_old_key_returns_original_result_without_allocating_capacity(): void
    {
        $service = $this->service();
        $lock = new InspectingServiceLock($service);
        $old = new Booking(['id' => 'old', 'status' => 'expired']);
        $repo = $this->createMock(ReservationRepository::class);
        $repo->expects(self::once())->method('findByKey')->willReturnCallback(function () use ($lock, $old) {
            self::assertTrue($lock->active);

            return $old;
        });
        $repo->expects(self::never())->method('findActive');
        $repo->expects(self::never())->method('occupied');
        $repo->expects(self::never())->method('create');
        $action = new ReserveBooking($repo, $lock, new ReservationPolicy);
        self::assertSame($old, $action->execute(new User(['id' => 1]), $service, 'old-key'));
    }

    public function test_repeated_action_with_new_key_is_permanently_mapped_to_active_booking(): void
    {
        $service = $this->service();
        $lock = new InspectingServiceLock($service);
        $active = new Booking(['id' => 'active', 'status' => 'pending']);
        $repo = $this->createMock(ReservationRepository::class);
        $repo->method('findByKey')->willReturn(null);
        $repo->method('findActive')->willReturn($active);
        $repo->expects(self::once())->method('rememberKey')->with(1, 1, 'new-key', $active);
        $repo->expects(self::never())->method('create');
        self::assertSame($active, (new ReserveBooking($repo, $lock, new ReservationPolicy))->execute(new User(['id' => 1]), $service, 'new-key'));
    }

    public function test_full_inventory_never_creates_a_booking(): void
    {
        $service = $this->service();
        $repo = $this->createMock(ReservationRepository::class);
        $repo->method('occupied')->willReturn(1);
        $repo->expects(self::never())->method('create');
        $this->expectException(BookingConflict::class);
        (new ReserveBooking($repo, new InspectingServiceLock($service), new ReservationPolicy))->execute(new User(['id' => 1]), $service, 'key');
    }

    public function test_creation_and_key_mapping_share_lock_and_deadline_is_capped(): void
    {
        $service = $this->service();
        $lock = new InspectingServiceLock($service);
        $repo = $this->createMock(ReservationRepository::class);
        $booking = new Booking(['id' => 'new']);
        $repo->method('occupied')->willReturn(0);
        $repo->expects(self::once())->method('create')->willReturnCallback(function ($user, $current, $key, CarbonImmutable $expires) use ($lock, $service, $booking) {
            self::assertTrue($lock->active);
            self::assertTrue($expires->equalTo($service->ends_at));

            return $booking;
        });
        $repo->expects(self::once())->method('rememberKey')->willReturnCallback(function () use ($lock) {
            self::assertTrue($lock->active);
        });
        self::assertSame($booking, (new ReserveBooking($repo, $lock, new ReservationPolicy))->execute(new User(['id' => 1]), $service, 'key'));
        self::assertFalse($lock->active);
    }

    public function test_cancel_reloads_state_under_lock_and_rejects_already_confirmed_booking(): void
    {
        $lock = new InspectingServiceLock($this->service());
        $store = $this->createMock(BookingStore::class);
        $store->expects(self::once())->method('get')->willReturnCallback(function () use ($lock) {
            self::assertTrue($lock->active);

            return new Booking(['id' => 'one', 'status' => 'confirmed']);
        });
        $store->expects(self::never())->method('update');
        $this->expectException(BookingConflict::class);
        (new CancelBooking($store, $lock))->execute(new Booking(['id' => 'one', 'service_id' => 1, 'status' => 'pending']));
    }
}
