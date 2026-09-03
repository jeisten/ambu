<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('remission.{remissionId}', function ($user, $remissionId) {
    if ($user->isAdmin()) {
        return true;
    }

    $remission = \App\Models\Remission::find($remissionId);

    return $remission && (int) $user->id === (int) $remission->driver_id;
});

Broadcast::channel('ambulance.{ambulanceId}', function ($user, $ambulanceId) {
    return (bool) $user->is_active;
});

