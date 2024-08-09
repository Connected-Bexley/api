<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\File;
use App\Models\User;
use App\Models\Service;
use Carbon\CarbonImmutable;
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
            'alt_text' => 'image description',
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
            'alt_text' => 'image description',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $service->logo_file_id = $this->getResponseContent($response, 'data.id');
        $service->save();

        $response = $this->get("/core/v1/services/$service->id/logo.png");

        $this->assertEquals($image, $response->content());

        $response = $this->get("/core/v1/files/$service->logo_file_id");

        $response->assertJson([
            'data' => [
                'id' => $service->logoFile->id,
                'is_private' => false,
                'mime_type' => 'image/png',
                'alt_text' => 'image description',
                'max_dimension' => null,
                'src' => 'data:image/png;base64,' . base64_encode($image),
                'url' => $service->logoFile->url(),
                'created_at' => $service->logoFile->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $service->logoFile->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
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
            'alt_text' => 'image description',
            'file' => 'data:image/jpeg;base64,' . base64_encode($image),
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $service->logo_file_id = $this->getResponseContent($response, 'data.id');
        $service->save();

        $response = $this->get("/core/v1/services/$service->id/logo.jpg");

        $this->assertEquals($image, $response->content());

        $response = $this->get("/core/v1/files/$service->logo_file_id");

        $response->assertJson([
            'data' => [
                'id' => $service->logoFile->id,
                'is_private' => false,
                'mime_type' => 'image/jpeg',
                'alt_text' => 'image description',
                'max_dimension' => null,
                'src' => 'data:image/jpeg;base64,' . base64_encode($image),
                'url' => $service->logoFile->url(),
                'created_at' => $service->logoFile->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $service->logoFile->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function createSvgFileAsServiceAdmin201(): void
    {
        $image = Storage::disk('local')->get('/test-data/image.svg');

        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/svg+xml',
            'alt_text' => 'image description',
            'file' => 'data:image/svg+xml;base64,' . base64_encode($image),
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $service->logo_file_id = $this->getResponseContent($response, 'data.id');
        $service->save();

        $response = $this->get("/core/v1/services/$service->id/logo.svg");

        $this->assertEquals($image, $response->content());
        $response = $this->get("/core/v1/files/$service->logo_file_id");

        $response->assertJson([
            'data' => [
                'id' => $service->logoFile->id,
                'is_private' => false,
                'mime_type' => 'image/svg+xml',
                'alt_text' => 'image description',
                'max_dimension' => null,
                'src' => 'data:image/svg+xml;base64,' . base64_encode($image),
                'url' => $service->logoFile->url(),
                'created_at' => $service->logoFile->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $service->logoFile->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function createImageFileAltTextRequiredAsServiceAdmin201(): void
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

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'alt_text' => '',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/jpeg',
            'alt_text' => '',
            'file' => 'data:image/jpeg;base64,' . base64_encode($image),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/svg+xml',
            'alt_text' => '',
            'file' => 'data:image/svg+xml;base64,' . base64_encode($image),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function getPngFileAsGuest200()
    {
        $image = File::factory()->pendingAssignment()->imagePng()->create();
        $service = Service::factory()->create([
            'logo_file_id' => $image->id,
        ]);

        $response = $this->get("/core/v1/files/$image->id");

        $response->assertJson([
            'data' => [
                'id' => $image->id,
                'is_private' => $image->is_private,
                'mime_type' => $image->mime_type,
                'alt_text' => $image->meta['alt_text'],
                'max_dimension' => null,
                'src' => 'data:image/png;base64,' . base64_encode($image->getContent()),
                'url' => $image->url(),
                'created_at' => $image->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $image->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);

        $this->assertEquals('data:image/png;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.png')), $response->json('data.src'));
    }

    /**
     * @test
     */
    public function getJpgFileAsGuest200()
    {
        $image = File::factory()->pendingAssignment()->imageJpg()->create();
        $service = Service::factory()->create([
            'logo_file_id' => $image->id,
        ]);

        $response = $this->get("/core/v1/files/$image->id");

        $response->assertJson([
            'data' => [
                'id' => $image->id,
                'is_private' => $image->is_private,
                'mime_type' => $image->mime_type,
                'alt_text' => $image->meta['alt_text'],
                'max_dimension' => null,
                'src' => 'data:image/jpeg;base64,' . base64_encode($image->getContent()),
                'url' => $image->url(),
                'created_at' => $image->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $image->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);

        $this->assertEquals('data:image/jpeg;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.jpg')), $response->json('data.src'));
    }

    /**
     * @test
     */
    public function getSvgFileAsGuest200()
    {
        $image = File::factory()->pendingAssignment()->imageSvg()->create();
        $service = Service::factory()->create([
            'logo_file_id' => $image->id,
        ]);

        $response = $this->get("/core/v1/files/$image->id");

        $response->assertJson([
            'data' => [
                'id' => $image->id,
                'is_private' => $image->is_private,
                'mime_type' => $image->mime_type,
                'alt_text' => $image->meta['alt_text'],
                'max_dimension' => null,
                'src' => 'data:image/svg+xml;base64,' . base64_encode($image->getContent()),
                'url' => $image->url(),
                'created_at' => $image->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $image->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);

        $this->assertEquals('data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg')), $response->json('data.src'));
    }

    /**
     * @test
     */
    public function displayPngFileAsGuest200()
    {
        $image = File::factory()->pendingAssignment()->imagePng()->create();
        $service = Service::factory()->create([
            'logo_file_id' => $image->id,
        ]);

        $response = $this->get("/core/v1/images/$image->filename");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertHeader('Content-Disposition', 'inline; filename=' . $image->filename);

        $this->assertEquals(Storage::disk('local')->get('/test-data/image.png'), $response->streamedContent());
    }

    /**
     * @test
     */
    public function displayJpegFileAsGuest200()
    {
        $image = File::factory()->pendingAssignment()->imageJpg()->create();
        $service = Service::factory()->create([
            'logo_file_id' => $image->id,
        ]);

        $response = $this->get("/core/v1/images/$image->filename");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertHeader('Content-Disposition', 'inline; filename=' . $image->filename);

        $this->assertEquals(Storage::disk('local')->get('/test-data/image.jpg'), $response->streamedContent());
    }

    /**
     * @test
     */
    public function displaySvgFileAsGuest200()
    {
        $image = File::factory()->pendingAssignment()->imageSvg()->create();
        $service = Service::factory()->create([
            'logo_file_id' => $image->id,
        ]);

        $response = $this->get("/core/v1/images/$image->filename");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertHeader('Content-Disposition', 'inline; filename=' . $image->filename);

        $this->assertEquals(Storage::disk('local')->get('/test-data/image.svg'), $response->streamedContent());
    }
}
