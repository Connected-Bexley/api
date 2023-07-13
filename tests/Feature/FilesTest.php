<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class FilesTest extends TestCase
{
    /**
     * Create a file
     */

    /**
     * @test
     */
    public function createFileAsGuest403()
    {
        $image = Storage::disk('local')->get('/test-data/image.png');

        $response = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function createFileAsServiceAdmin201()
    {
        $image = Storage::disk('local')->get('/test-data/image.png');

        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $service->logo_file_id = $this->getResponseContent($response, 'data.id');
        $service->save();

        $response = $this->get("/core/v1/services/$service->id/logo.png");

        $this->assertEquals($image, $response->content());
    }
}
