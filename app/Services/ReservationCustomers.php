<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Database\UniqueConstraintViolationException;

class ReservationCustomers
{
    /** Resolve the client inside the reservation confirmation transaction. */
    public function resolve(Reservation $reservation): Customer
    {
        $customer = $reservation->customer_id
            ? Customer::withTrashed()->whereKey($reservation->customer_id)->lockForUpdate()->firstOrFail()
            : Customer::withTrashed()->where('phone', $reservation->phone)->lockForUpdate()->first();

        if (! $customer) {
            $email = $reservation->email ? mb_strtolower(trim($reservation->email)) : null;
            $attributes = ['first_name' => $reservation->customer_name, 'email' => $email];

            try {
                $customer = Customer::withTrashed()->lockForUpdate()->createOrFirst(['phone' => $reservation->phone], $attributes);
            } catch (UniqueConstraintViolationException $exception) {
                if (! $email || ! Customer::withTrashed()->where('email', $email)->lockForUpdate()->first()) {
                    throw $exception;
                }

                // A shared email must not merge clients with different phone numbers.
                $customer = Customer::withTrashed()->lockForUpdate()->createOrFirst(['phone' => $reservation->phone], [...$attributes, 'email' => null]);
            }
        }

        if ($customer->trashed()) {
            $customer->restore();
        }

        return $customer;
    }
}
