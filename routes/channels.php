<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('progress.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});