<?php

namespace App\Contract\Setting;

use App\Contract\BaseContract;

interface UserContract extends BaseContract
{
    /**
     * Clear a user's two-factor enrolment so they can sign in with their
     * password alone and enrol again. The support path for someone who has
     * lost both their device and their recovery codes.
     */
    public function resetTwoFactor($id);
}
