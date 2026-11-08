<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user->is_active && ! $user->must_change_password && ! $user->pending_session_choice_id
        && (int) $user->id === (int) $id;
});

Broadcast::channel('users.{userId}', function ($user, $userId) {
    return $user->is_active && ! $user->must_change_password && ! $user->pending_session_choice_id
        && (int) $user->id === (int) $userId;
});
