<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\Service;
use App\Models\ServiceLocation;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createOrganisations();
    }

    protected function createOrganisations(int $count = 10)
    {
        $organisations = Organisation::factory()->count($count)->create();
        $services = [];

        foreach ($organisations as $organisation) {
            $createdServices = $this->createServices($organisation);
            foreach ($createdServices as $createdService) {
                $services[] = $createdService;
            }
        }

        foreach ($services as $service) {
            $this->createServiceLocations($service);
        }
    }

    /**
     * @return mixed
     */
    protected function createServices(Organisation $organisation, int $count = 5)
    {
        return Service::factory()->count($count)->create(['organisation_id' => $organisation->id]);
    }

    /**
     * @return mixed
     */
    protected function createServiceLocations(Service $service, int $count = 2)
    {
        return ServiceLocation::factory()->count($count)->create(['service_id' => $service->id]);
    }
}
