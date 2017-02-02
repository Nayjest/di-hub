<?php

namespace Nayjest\DI;

class ComponentMethodNaming
{
    private function __construct()
    {
    }

    public static function getter(Definition $d) {
        return 'get' . ucfirst($d->localId ?: $d->id);
    }

    public static function setter(Definition $d) {
        return 'set' . ucfirst($d->localId ?: $d->id);
    }

    public static function tracks(Definition $d, $trackedId)
    {
        return 'push' . ucfirst($trackedId) . 'To' . ucfirst($d->localId ?: $d->id);
    }

    public static function trackedBy(Definition $d, $trackedById)
    {
        return 'push' . ucfirst($d->localId ?: $d->id) . 'To' . ucfirst($trackedById);
    }

}