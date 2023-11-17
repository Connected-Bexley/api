<?php

namespace App\Models;

use App\Models\Mutators\OrganisationTaxonomyMutators;
use App\Models\Relationships\OrganisationTaxonomyRelationships;
use App\Models\Scopes\OrganisationTaxonomyScopes;

class OrganisationTaxonomy extends Model
{
    use OrganisationTaxonomyMutators;
    use OrganisationTaxonomyRelationships;
    use OrganisationTaxonomyScopes;

    public function touchOrganisation(): OrganisationTaxonomy
    {
        $this->organisation->save();

        return $this;
    }
}
