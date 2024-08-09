<?php

namespace App\Events;

use App\Models\Audit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use Laravel\Passport\Client;

class EndpointHit
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var User|null
     */
    protected $user;

    /**
     * @var Client|null
     */
    protected $oauthClient;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string
     */
    protected $ipAddress;

    /**
     * @var string
     */
    protected $userAgent;

    /**
     * @var CarbonImmutable
     */
    protected $createdAt;

    /**
     * @var \App\Models\Model|null
     */
    protected $model;

    /**
     * Create a new event instance.
     */
    protected function __construct(Request $request, string $action, string $description, Model $model = null)
    {
        $user = $request->user('api');

        $this->user = $user;
        $this->oauthClient = $user?->token()->client ?? null;
        $this->action = $action;
        $this->description = $description;
        $this->ipAddress = $request->ip();
        $this->userAgent = $request->userAgent();
        $this->createdAt = Date::now();
        $this->model = $model;
    }

    public static function onCreate(Request $request, string $message, Model $model = null): self
    {
        return new static($request, Audit::ACTION_CREATE, $message, $model);
    }

    public static function onRead(Request $request, string $message, Model $model = null): self
    {
        return new static($request, Audit::ACTION_READ, $message, $model);
    }

    public static function onUpdate(Request $request, string $message, Model $model = null): self
    {
        return new static($request, Audit::ACTION_UPDATE, $message, $model);
    }

    public static function onDelete(Request $request, string $message, Model $model = null): self
    {
        return new static($request, Audit::ACTION_DELETE, $message, $model);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getOauthClient(): ?Client
    {
        return $this->oauthClient;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function isFor(string $model, string $action = null): bool
    {
        return $action
            ? ($this->getModel() instanceof $model) && $this->getAction() === $action
            : $this->getModel() instanceof $model;
    }

    public function isntFor(string $model, string $action = null): bool
    {
        return !$this->isFor($model, $action);
    }
}
