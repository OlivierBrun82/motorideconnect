<?php

namespace App\Exception;

// Exception métier levée quand une (dés)inscription est refusée
// (balade complète, déjà inscrit, organisateur, etc.)
class RideRegistrationException extends \RuntimeException
{
}