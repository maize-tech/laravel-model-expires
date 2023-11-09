<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Expiration model
    |--------------------------------------------------------------------------
    |
    | Here you may specify the fully qualified class name of the expiration model.
    |
    */

    'expiration_model' => Maize\ModelExpires\Models\Expiration::class,

    'model' => [

        /*
        |--------------------------------------------------------------------------
        | Expires after days
        |--------------------------------------------------------------------------
        |
        | Here you may specify the default amount of days after which a model
        | should expire.
        | If null, all newly created models won't have a default expiration date.
        |
        */

        'expires_after_days' => null,

        /*
        |--------------------------------------------------------------------------
        | Deletes after days
        |--------------------------------------------------------------------------
        |
        | Here you may specify the default amount of days after which a model
        | should be deleted.
        | If null, all newly created models won't have a default deletion date.
        |
        */

        'deletes_after_days' => null,
    ],

    'expiring_notification' => [

        /*
        |--------------------------------------------------------------------------
        | Enable expiring notification
        |--------------------------------------------------------------------------
        |
        | Here you may specify whether you want to enable model expiring
        | notifications or not.
        |
        */

        'enabled' => true,

        /*
        |--------------------------------------------------------------------------
        | Notification class
        |--------------------------------------------------------------------------
        |
        | Here you may specify the fully qualified class name of the default notification.
        | If null, no notifications will be sent.
        |
        */

        'notification' => Maize\ModelExpires\Notifications\ModelExpiringNotification::class,

        /*
        |--------------------------------------------------------------------------
        | Notifiable emails
        |--------------------------------------------------------------------------
        |
        | Here you may specify the default list of notifiable email addresses.
        |
        */

        'notifiables' => [
            //
        ],
    ],
];
