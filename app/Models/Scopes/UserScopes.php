<?php

namespace App\Models\Scopes;

use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait UserScopes
{
    public function scopeGlobalAdmins(Builder $query): Builder
    {
        return $query->whereHas('userRoles', function (Builder $query) {
            return $query->where(table(UserRole::class, 'role_id'), Role::globalAdmin()->id);
        });
    }

    public function scopeContentAdmins(Builder $query): Builder
    {
        return $query->whereHas('userRoles', function (Builder $query) {
            return $query->where(table(UserRole::class, 'role_id'), Role::contentAdmin()->id);
        });
    }

    public function scopeWithHighestRoleOrder(Builder $query, string $alias = 'highest_role_order'): Builder
    {
        $sql = $this->getHighestRoleOrderSql();

        $subQuery = DB::table('user_roles')
            ->selectRaw($sql['sql'], $sql['bindings'])
            ->whereRaw('`user_roles`.`user_id` = `users`.`id`')
            ->orderByRaw($sql['sql'], $sql['bindings'])
            ->take(1);

        return $query->selectRaw(
            "({$subQuery->toSql()}) AS `{$alias}`",
            $subQuery->getBindings()
        );
    }

    /**
     * This SQL query is placed into its own method as it is referenced
     * in multiple places.
     */
    public function getHighestRoleOrderSql(): array
    {
        $sql = <<<'EOT'
CASE `user_roles`.`role_id`
    WHEN ? THEN 1
    WHEN ? THEN 2
    WHEN ? THEN 3
    WHEN ? THEN 4
    WHEN ? THEN 5
    WHEN ? THEN 6
    ELSE 7
END
EOT;

        $bindings = [
            Role::superAdmin()->id,
            Role::organisationAdmin()->id,
            Role::serviceAdmin()->id,
            Role::serviceWorker()->id,
            Role::globalAdmin()->id,
            Role::contentAdmin()->id,
        ];

        return compact('sql', 'bindings');
    }
}
