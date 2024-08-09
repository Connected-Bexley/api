<?php

namespace App\Console\Commands\Ck;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ck:create-user
        {first_name : The user\'s first name}
        {last_name : The user\' last name}
        {email : The user\'s email}
        {phone : The user\'s phone number}
        {--password= : The user\'s password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new user with Super Admin privileges';

    /**
     * @var DatabaseManager
     */
    protected $db;

    /**
     * CreateUserCommand constructor.
     */
    public function __construct(DatabaseManager $db)
    {
        parent::__construct();

        $this->db = $db;
    }

    /**
     * Execute the console command.
     *
     * @throws \Throwable
     */
    public function handle(): int
    {
        return $this->db->transaction(function () {
            // Cache the password to display.
            $password = $this->option('password') ?? Str::random(16);

            // Create the user record.
            $user = $this->createUser($password);

            // Make the user a Super Admin.
            $user->makeSuperAdmin();

            // Output message.
            $this->info('User created successfully.');
            $this->warn("Password: $password");

            return true;
        });
    }

    protected function createUser(string $password): User
    {
        return User::create([
            'first_name' => $this->argument('first_name'),
            'last_name' => $this->argument('last_name'),
            'email' => $this->argument('email'),
            'phone' => $this->argument('phone'),
            'password' => bcrypt($password),
        ]);
    }
}
