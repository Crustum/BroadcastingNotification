<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity for Testing
 */
class User extends Entity
{
    /**
     * The channel the user receives notification broadcasts on
     *
     * Returns the private channel name for this user's broadcast notifications.
     *
     * @return string
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.' . $this->id;
    }
}
