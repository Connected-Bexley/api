<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Storage;

class FilesTest extends TestCase
{
    /**
     * Create a file
     */

    /**
     * @test
     */
    public function createFileAsGuest403(): void
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
    public function createPngFileAsServiceAdmin201(): void
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

    /**
     * @test
     */
    public function createJpgFileAsServiceAdmin201(): void
    {
        $image = Storage::disk('local')->get('/test-data/image.jpg');

        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/jpeg',
            'file' => 'data:image/jpeg;base64,' . base64_encode($image),
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $service->logo_file_id = $this->getResponseContent($response, 'data.id');
        $service->save();

        $response = $this->get("/core/v1/services/$service->id/logo.jpg");

        $this->assertEquals($image, $response->content());
    }
}
