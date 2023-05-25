<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceRefreshToken extends Model
{
    use HasFactory;
    use Mutators\ServiceRefreshTokenMutators;
    use Relationships\ServiceRefreshTokenRelationships;
    use Scopes\ServiceRefreshTokenScopes;

    const AUTO_DELETE_MONTHS = 1;
}
