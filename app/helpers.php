<?php

use App\Http\Resources\UserResource;
use App\Models\AdministrativeStatus;
use App\Models\Family;
use App\Models\Force;
use App\Models\IdentifierType;
use App\Models\Menu;
use App\Models\PendingTeacher;
use App\Models\Specialty;
use App\Models\Staff;
use App\Models\StaffType;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserType;
use App\Tenant;
use PulkitJalan\Google\Client;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create model.
 *
 * @param $class
 * @param array $attributes
 * @param int $times
 * @return mixed
 */
function create($class, $attributes = [], $times = 1)
{
    $data = factory($class)->times($times)->create($attributes);
    if ($times > 1) {
        return $data;
    }
    return $data->first();
}

if (! function_exists('scool_menu')) {

    /**
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    function scool_menu()
    {
        return Menu::all();
    }
}

if (! function_exists('create_tenant')) {

    /**
     * Create Tenant.
     *
     * @param $name
     * @param $subdomain
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    function create_tenant($name,$subdomain)
    {
        return Tenant::create([
            'name' => $name,
            'subdomain' => $subdomain,
            'hostname' => 'localhost',
            'username' => $subdomain,
            'password' => 'secret',
            'database' => $subdomain,
            'port' => 3306
        ]);
    }
}

if (! function_exists('tenant_connect')) {
    /**
     * Establish a tenant database connection.
     *
     * @param $hostname
     * @param $username
     * @param $password
     * @param $database
     */
    function tenant_connect($hostname, $username, $password, $database)
    {
        // Erase the tenant connection, thus making Laravel get the default values all over again.
        DB::purge('tenant');

        // Make sure to use the database name we want to establish a connection.
        Config::set('database.connections.tenant.host', $hostname);
        Config::set('database.connections.tenant.database', $database);
        Config::set('database.connections.tenant.username', $username);
        Config::set('database.connections.tenant.password', $password);

        // Rearrange the connection data
        DB::reconnect('tenant');

        // Ping the database. This will throw an exception in case the database does not exists.
        Schema::connection('tenant')->getConnection()->reconnect();
    }
}

if (! function_exists('main_connect')) {
    function main_connect()
    {
        // Erase the tenant connection, thus making Laravel get the default values all over again.
        DB::purge('tenant');

        Config::set('database.default',env('DB_CONNECTION', 'mysql'));

        // Ping the database. This will throw an exception in case the database does not exists.
        Schema::connection(config('database.default'))->getConnection()->reconnect();
    }
}

if (! function_exists('create_admin_user_on_tenant')) {
    /**
     * @param $user
     * @param $tenant
     */
    function create_admin_user_on_tenant($user, $tenant, $password = null)
    {
        tenant_connect(
            $tenant->hostname,
            $tenant->username,
            $tenant->password,
            $tenant->database
        );

        if(!$password) $password = str_random();

        $existingUser = App\Models\User::where('email',$user->email)->first();

        if (!$existingUser) {
            User::forceCreate([
                'name' => $user->name,
                'email' => $user->email,
                'password' => bcrypt($password),
                'admin' => true
            ]);
        }
        DB::purge('tenant');
    }
}

if (! function_exists('create_admin_user')) {
    /**
     *
     */
    function create_admin_user()
    {
        if (! App\User::where('email',env('ADMIN_USER_EMAIL','sergiturbadenas@gmail.com'))->first()) {
            App\User::forceCreate([
                'name' => env('ADMIN_USER_NAME','Sergi Tur Badenas'),
                'email' => env('ADMIN_USER_EMAIL','sergiturbadenas@gmail.com'),
                'password' => bcrypt(env('ADMIN_USER_PASSWORD','123456')),
                'admin' => true
            ]);
        }
    }
}

if (! function_exists('create_tenant_admin_user')) {
    /**
     *
     */
    function create_tenant_admin_user()
    {
        if (! App\Models\User::where('email',env('ADMIN_USER_EMAIL','sergiturbadenas@gmail.com'))->first()) {
            App\Models\User::forceCreate([
                'name' => env('ADMIN_USER_NAME','Sergi Tur Badenas'),
                'email' => env('ADMIN_USER_EMAIL','sergiturbadenas@gmail.com'),
                'password' => sha1(env('ADMIN_USER_PASSWORD','123456')),
                'admin' => true
            ]);
        }
    }
}

if (! function_exists('create_default_tenant')) {
    function create_default_tenant() {
        $user = App\User::find(1);
        $tenant = Tenant::where('subdomain','iesebre')->first();
        if (! $tenant) {
            $tenant = $user->addTenant($tenant = Tenant::create([
                'name' => "Institut de l'Ebre",
                'subdomain' => 'iesebre',
                'hostname' => 'localhost',
                'database' => 'iesebre',
                'username' => 'iesebre',
                'password' => str_random(),
                'port' => 3306,
                'gsuite_service_account_path' => '/gsuite_service_accounts/scool-07eed0b550a6f.json',
                'gsuite_admin_email' => 'sergitur@iesebre.com'
            ]));
        }

        create_mysql_full_database(
            $tenant->database,
            $tenant->username ,
            $tenant->password,
            $tenant->hostname);

        create_admin_user_on_tenant($tenant->user, $tenant, env('ADMIN_USER_PASSWORD','123456'));

        DB::purge('tenant');

        main_connect();
    }
}


if (! function_exists('create_admin_user_on_subdomain')) {

    /**
     * @param $subdomain
     */
    function create_admin_user_on_subdomain($subdomain)
    {
        $tenant = Tenant::findBySubdomain($subdomain);
        tenant_connect(
            $tenant->hostname,
            $tenant->username,
            $tenant->password,
            $tenant->database
        );

        User::forceCreate([
            'name' => env('ADMIN_USER_NAME_ON_TENANT','Sergi Tur Badenas'),
            'email' => env('ADMIN_USER_EMAIL_ON_TENANT','sergiturbadenas@gmail.com'),
            'password' => sha1(env('ADMIN_USER_PASSWORD_ON_TENANT','123456')),
            'admin' => true
        ]);
    }
}

if (! function_exists('save_current_tenant_config')) {
    /**
     * @return object
     */
    function save_current_tenant_config()
    {
        $host = Config::get('database.connections.tenant.host');
        $database = Config::get('database.connections.tenant.database');
        $username = Config::get('database.connections.tenant.username');
        $password = Config::get('database.connections.tenant.password');

        return (object) compact('host', 'database', 'username', 'password');
    }
}

if (! function_exists('restore_current_tenant_config')) {
    /**
     * @param $oldConfig
     */
    function restore_current_tenant_config($oldConfig)
    {
        Config::set('database.connections.tenant.host', $oldConfig->host);
        Config::set('database.connections.tenant.database', $oldConfig->database);
        Config::set('database.connections.tenant.username', $oldConfig->username);
        Config::set('database.connections.tenant.password', $oldConfig->password);
    }
}

if (! function_exists('test_user')) {
    /**
     * @param $user
     * @param $tenant
     * @return array
     */
    function test_user($user, $tenant, $password) {
        $current_config = save_current_tenant_config();
        $result = [];
        try {
            tenant_connect($tenant->hostname, $tenant->username, $tenant->password, $tenant->database);

            $tenantUser = User::where('email',$user->email)->firstOrFail();

            if (Hash::check($password, $tenantUser->password)) {
                $result = [ 'connection' => 'ok' ];
            } else {
                $result = [
                    'connection' => 'Error',
                    'exception' => 'Password incorrect for user ' . $user->email
                ];
            }


        } catch (PDOException $e) {
            $result = [
                'connection' => 'Error',
                'exception' => $e->getMessage()
            ];
        }

        restore_current_tenant_config($current_config);
        return $result;
    }
}

if (! function_exists('test_connection')) {
    /**
     * @param $hostname
     * @param $username
     * @param $password
     * @param $database
     * @return array
     */
    function test_connection($hostname, $username, $password, $database)
    {
        $current_config = save_current_tenant_config();
        $result = [];
        try {
            tenant_connect($hostname, $username, $password, $database);
            $result = [ 'connection' => 'ok' ];
        } catch (PDOException $e) {
            $result = [
                'connection' => 'Error',
                'exception' => $e->getMessage()
            ];
        }
        restore_current_tenant_config($current_config);

        return $result;
    }
}


if (! function_exists('tenant_migrate')) {
    /**
     * Run Tenant Migrations in the connected tenant database.
     */
    function tenant_migrate()
    {
        Config::set('auth.providers.users.model',User::class);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant'
        ]);
    }
}

if (! function_exists('tenant_seed')) {
    /**
     * Run Tenant Migrations in the connected tenant database.
     */
    function tenant_seed()
    {
        Config::set('auth.providers.users.model',User::class);

        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => 'TenantDatabaseSeeder'
        ]);
    }
}

/**
 * @param $name
 * @param $user
 * @param null $password
 * @return null|string
 */
function create_mysql_full_database($name, $user , $password = null, $host = null)
{
    create_mysql_database($name);
    $password = create_mysql_user($user, $password, $host);
    mysql_grant_privileges($user, $name, $host);

    $hostname = 'localhost';
    if ($host) $hostname = $host;
    tenant_connect($hostname, $name, $password, $name);
    tenant_migrate();
    tenant_seed();

    return $password;
}

if (! function_exists('tenant_connect_migrate_seed')) {

    /**
     * @param $name
     */
    function tenant_connect_migrate_seed($name)
    {
        $tenant = Tenant::findBySubDomain($name);
        tenant_connect($tenant->hostname, $tenant->subdomain, $tenant->password, $tenant->subdomain);
        tenant_migrate();
        tenant_seed();
    }
}

/**
 * @param $name
 * @param $user
 * @param null $password
 * @return null|string
 */
function delete_mysql_full_database($name, $user, $host = null)
{
    delete_mysql_database($name);
    delete_mysql_user($user, $host);
}

/**
 *
 */
function set_mysql_admin_connection() {
    DB::purge('mysql');

    Config::set('database.connections.mysql.host', env('MYSQL_ADMIN_HOST'));
    Config::set('database.connections.mysql.port', env('MYSQL_ADMIN_PORT'));
    Config::set('database.connections.mysql.database', null);
    Config::set('database.connections.mysql.username', env('MYSQL_ADMIN_USERNAME'));
    Config::set('database.connections.mysql.password', env('MYSQL_ADMIN_PASSWORD'));

    // Rearrange the connection data
    DB::reconnect('mysql');

    // Ping the database. This will throw an exception in case the database does not exists.
    Schema::connection('mysql')->getConnection()->reconnect();
}

/**
 * @param $name
 */
function create_mysql_database($name)
{
    set_mysql_admin_connection();
    DB::connection('mysql')->getPdo()->exec("CREATE DATABASE IF NOT EXISTS `{$name}`");
}

/**
 * @param $name
 */
function delete_mysql_database($name)
{
    set_mysql_admin_connection();
    DB::connection('mysql')->getPdo()->exec("DROP DATABASE IF EXISTS `{$name}`");
}

/**
 * @param $name
 */
function remove_mysql_database($name)
{
    set_mysql_admin_connection();
    DB::connection('mysql')->getPdo()->exec("DROP DATABASE IF EXISTS `{$name}`");
}

/**
 * @param $name
 * @param null $password
 * @param string $host
 * @return null|string
 */
function create_mysql_user($name, $password = null, $host = 'localhost')
{
    set_mysql_admin_connection();
    if(!$password) $password = str_random();
    DB::connection('mysql')->getPdo()->exec(
        "CREATE USER IF NOT EXISTS '{$name}'@'{$host}'");
    DB::connection('mysql')->getPdo()->exec(
        "ALTER USER '{$name}'@'{$host}' IDENTIFIED BY '{$password}'");
    return $password;
}

/**
 * @param $name
 * @param null $password
 * @param string $host
 * @return null|string
 */
function delete_mysql_user($name, $host = 'localhost')
{
    set_mysql_admin_connection();
    DB::connection('mysql')->getPdo()->exec(
        "DROP USER IF EXISTS '{$name}'@'{$host}'");
}

/**
 * @param $user
 * @param string $host
 */
function mysql_grant_all_privileges($user, $host = 'localhost') {
    set_mysql_admin_connection();
    DB::connection('mysql')->getPdo()->exec(
        "GRANT ALL PRIVILEGES ON *.* TO '{$user}'@'{$host}' WITH GRANT OPTION");
    DB::connection('mysql')->getPdo()->exec("FLUSH PRIVILEGES");
}

/**
 * @param $user
 * @param $database
 * @param string $host
 */
function mysql_grant_privileges($user, $database, $host = 'localhost') {
    set_mysql_admin_connection();
    DB::connection('mysql')->getPdo()->exec(
        "GRANT ALL PRIVILEGES ON {$database}.* TO '{$user}'@'{$host}' WITH GRANT OPTION");
    DB::connection('mysql')->getPdo()->exec("FLUSH PRIVILEGES");
}

if (!function_exists('get_tenant')) {
    /**
     * @param $name
     * @return mixed
     */
    function get_tenant($name) {
        return \App\Tenant::where('subdomain', $name)->firstOrFail();
    }
}


if (!function_exists('formatted_logged_user')) {
    function formatted_logged_user()
    {
        return json_encode((new UserResource(Auth::user()))->resolve());
    }
}

if (!function_exists('initialize_tenant_roles_and_permissions')) {
    function initialize_tenant_roles_and_permissions()
    {
        $roles = [
            'Student',
            'Teacher',
            'Janitor',
            'AdministrativeAssistant',
            'Familiar',
            'Manager',
            'Admin',
            'UsersManager',
            'StaffManager',
            'TeachersManager',
            'PhotoTeachersManager'
        ];

        // Manager
        // - Rol assignat a l'usuari principal (de fet és superadmin) però també es pot assignar a altres
        // - Menú administració:
        // - Gestió de mòduls

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $permissions = [
        ];


        foreach ($permissions as $permission) {
            $permission = Permission::firstOrCreate(['name' => $permission]);
        }
    }
}

if (!function_exists('initialize_gates')) {
    function initialize_gates()
    {
        Gate::define('store-user-photo', function ($user) {
            return $user->hasRole(['UsersManager','TeachersManager']);
        });


        Gate::define('show-users', function ($user) {
            return $user->hasRole('UsersManager');
        });

        Gate::define('create_users', function ($user) {
            return $user->hasRole('UsersManager');
        });

        Gate::define('delete_users', function ($user) {
            return $user->hasRole('UsersManager');
        });

        // Google suite Users
        Gate::define('list-gsuite-users', function ($user) {
            return $user->hasRole('UsersManager');
        });

        Gate::define('show-gsuite-users', function ($user) {
            return $user->hasRole('UsersManager');
        });

        Gate::define('store-gsuite-users', function ($user) {
            return $user->hasRole('UsersManager');
        });

        Gate::define('delete-gsuite-users', function ($user) {
            return $user->hasRole('UsersManager');
        });

        Gate::define('watch-gsuite-users', function ($user) {
            return $user->hasRole('UsersManager');
        });

        // STAFF
        Gate::define('show-staff', function ($user) {
            return $user->hasRole('StaffManager');
        });

        Gate::define('store-staff', function ($user) {
            return $user->hasRole('StaffManager');
        });

        Gate::define('delete-staff', function ($user) {
            return $user->hasRole('StaffManager');
        });

        //Teachers
        Gate::define('show-teachers', function ($user) {
            return $user->hasRole('TeachersManager');
        });

        Gate::define('show-pending-teachers', function ($user) {
            return $user->hasRole('TeachersManager');
        });

        Gate::define('delete-pending-teacher', function ($user) {
            return $user->hasRole('TeachersManager');
        });


        Gate::define('show-teachers-photos', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        Gate::define('store-teachers-photos', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        Gate::define('show-teacher-photo', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        Gate::define('download-teacher-photo', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        Gate::define('delete-teacher-photo', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        Gate::define('delete-all-teacher-photo', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        Gate::define('edit-teacher-photo', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        Gate::define('store-teacher-photo', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        Gate::define('list-teacher-photo', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        Gate::define('destroy-teacher-photo', function ($user) {
            return $user->hasRole('PhotoTeachersManager');
        });

        //Pending teachers
        Gate::define('list_pending_teachers', function ($user) {
            return $user->hasRole('TeachersManager');
        });

        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });


    }
}

if (!function_exists('initialize_menus')) {
    function initialize_menus() {
        Menu::firstOrCreate([
            'icon' => 'home',
            'text' => 'Principal',
            'href' => '/home'
        ]);

        Menu::firstOrCreate([
            'heading' => 'Administració',
            'role' => 'Manager'
        ]);

        Menu::firstOrCreate([
            'text' => 'Mòduls',
            'href' => '/modules',
            'role' => 'Manager'
        ]);

        Menu::firstOrCreate([
            'text' => 'Usuaris',
            'href' => '/users',
            'role' => 'UsersManager'
        ]);

        Menu::firstOrCreate([
            'text' => 'Plantilla',
            'href' => '/staff',
            'role' => 'StaffManager'
        ]);

        Menu::firstOrCreate([
            'text' => 'Professorat',
            'href' => '/teachers',
            'role' => 'TeachersManager'
        ]);

        Menu::firstOrCreate([
            'text' => 'Fotos Professorat',
            'href' => '/teachers_photos',
            'role' => 'PhotoTeachersManager'
        ]);


        Menu::firstOrCreate([
            'text' => 'Configuració general',
            'href' => '/config',
            'role' => 'Admin'
        ]);
    }
}



if (!function_exists('initialize_staff_types')) {
    function initialize_staff_types()
    {
        StaffType::firstOrCreate([
            'name' => 'Professor/a'
        ]);

        StaffType::firstOrCreate([
            'name' => 'Conserge'
        ]);

        StaffType::firstOrCreate([
            'name' => 'Administratiu/va'
        ]);
    }
}

if (!function_exists('initialize_users')) {
    function initialize_users()
    {

    }
}

if (!function_exists('collect_files')) {
    /**
     * Collect files.
     *
     * @param $path
     * @param string $disk
     * @return static
     */
    function collect_files($path, $disk = 'local')
    {
        $files = collect(File::allFiles(Storage::disk($disk)->path($path)))->map(function ($file) {
            return [
                'filename' => $filename = $file->getFilename(),
                'slug' => str_slug($filename,'-')
            ];
        });
        return $files;
    }
}

if (!function_exists('initialize_administrative_assistants')) {
    function initialize_administrative_assistants()
    {
        User::createIfNotExists([
            'name' => 'Pilar Vericat',
            'email' => 'pilarvericat@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('AdministrativeAssistant'))
            ->assignFullName([
                'givenName' => 'Pilar',
                'sn1' => 'Vericat',
                'sn2' => '',
            ])
            ->assignStaff(
                Staff::firstOrCreate([
                    'type_id' => StaffType::findByName('Administratiu/va')->id,
                    'code' => 'A1',
                    'order' => 1
                ])
            );

        User::createIfNotExists([
            'name' => 'Cinta Tomas',
            'email' => 'cintatomas@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('AdministrativeAssistant'))
            ->assignFullName([
                'givenName' => 'Cinta',
                'sn1' => 'Tomas',
                'sn2' => '',
            ])
            ->assignStaff(
                Staff::firstOrCreate([
                    'type_id' => StaffType::findByName('Administratiu/va')->id,
                    'code' => 'A2',
                    'order' => 1
                ])
            );

        User::createIfNotExists([
            'name' => 'Lluïsa Garcia',
            'email' => 'lluisagarcia@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('AdministrativeAssistant'))
            ->assignFullName([
                'givenName' => 'Lluisa',
                'sn1' => 'Garcia',
                'sn2' => '',
            ])
            ->assignStaff(
                Staff::firstOrCreate([
                    'type_id' => StaffType::findByName('Administratiu/va')->id,
                    'code' => 'A3',
                    'order' => 1
                ])
            );

        User::createIfNotExists([
            'name' => 'Sonia Alegria',
            'email' => 'soniaalegria@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('AdministrativeAssistant'))
            ->assignFullName([
                'givenName' => 'Sonia',
                'sn1' => 'Alegria',
                'sn2' => '',
            ])
            ->assignStaff(
                Staff::firstOrCreate([
                    'type_id' => StaffType::findByName('Administratiu/va')->id,
                    'code' => 'F4',
                    'order' => 1
                ])
            );
    }
}

if (!function_exists('initialize_janitors')) {
    function initialize_janitors()
    {
        User::createIfNotExists([
            'name' => 'Jaume Benaiges',
            'email' => 'jaumebenaiges@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret using SHA1 (blames Gsuite) instead of bcrypt
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Janitor'))
            ->assignFullName([
                'givenName' => 'Jaume',
                'sn1' => 'Benaiges',
                'sn2' => '',
            ])
            ->assignStaff(
                Staff::firstOrCreate([
                    'type_id' => StaffType::findByName('Conserge')->id,
                    'code' => 'C1',
                    'order' => 1
                ])
            );

        User::createIfNotExists([
            'name' => 'Jordi Caudet',
            'email' => 'jordicaudet@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Janitor'))
            ->assignFullName([
                'givenName' => 'Jordi',
                'sn1' => 'Caudet',
                'sn2' => '',
            ])
            ->assignStaff(
                Staff::firstOrCreate([
                    'type_id' => StaffType::findByName('Conserge')->id,
                    'code' => 'C2',
                    'order' => 2
                ])
            );

        User::createIfNotExists([
            'name' => 'Leonor Agramunt',
            'email' => 'leonoragramunt@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Janitor'))
            ->assignFullName([
                'givenName' => 'Leonor',
                'sn1' => 'Agramunt',
                'sn2' => '',
            ])
            ->assignStaff(
                Staff::firstOrCreate([
                    'type_id' => StaffType::findByName('Conserge')->id,
                    'code' => 'C3',
                    'order' => 3
                ])
            );
    }
}

if (!function_exists('initialize_teachers')) {
    function initialize_teachers()
    {
        User::createIfNotExists([
            'name' => 'Dolors Sanjuan Aubà',
            'email' => 'dolorssanjuanauba@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Dolors',
            'sn1' => 'Sanjuan',
            'sn2' => 'Aubà',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('CAS')->id,
                'family_id' => Family::findByCode('CA')->id,
                'code' => '002',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
            'code' => '002'
        ]));

        User::createIfNotExists([
            'name' => 'Nuria Bordes Vidal',
            'email' => 'nbordes@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Nuria',
            'sn1' => 'Bordes',
            'sn2' => 'Vidal',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('524')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '028',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '028'
            ]));

        User::createIfNotExists([
            'name' => 'Laura Llopis Lozano',
            'email' => 'laurallopis@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Laura',
            'sn1' => 'Llopis',
            'sn2' => 'Lozano',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('525')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '029',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '029',
            ]));

        User::createIfNotExists([
            'name' => 'Vicent Favà Figueres',
            'email' => 'vfava@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Vicent',
            'sn1' => 'Favà',
            'sn2' => 'Figueres',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('525')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '030',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '030'
            ]));

        User::createIfNotExists([
            'name' => 'Agustí Baubí Rovira',
            'email' => 'agustinbaubi@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Agustí',
            'sn1' => 'Baubí',
            'sn2' => 'Rovira',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('525')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '031',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '031'
            ]));

        User::createIfNotExists([
            'name' => 'Josep Joan Cid Castella',
            'email' => 'joancid1@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Josep Joan',
            'sn1' => 'Cid',
            'sn2' => 'Castellar',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('513')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '116',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '116'
            ]));

        User::createIfNotExists([
            'name' => 'Rafel Puig Rios',
            'email' => 'rafelpuig@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Rafel',
            'sn1' => 'Puig',
            'sn2' => 'Rios',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('602')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '032',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '032'
            ]));

        User::createIfNotExists([
            'name' => 'Laureà Ferré Menasanch',
            'email' => 'lferre@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Laureà',
            'sn1' => 'Ferré',
            'sn2' => 'Menasanch',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('602')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '033',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '033'
            ]));

        User::createIfNotExists([
            'name' => 'Manel Canalda Vidal',
            'email' => 'manelcanalda@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Manel',
            'sn1' => 'Canalda',
            'sn2' => 'Vidal',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('605')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '034',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '034'
            ]));

        User::createIfNotExists([
            'name' => 'Xavi Bel Fernández',
            'email' => 'xbel@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Xavi',
            'sn1' => 'Bel',
            'sn2' => 'Fernández',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('606')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '035',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '035'
            ]));

        User::createIfNotExists([
            'name' => 'J.Luís Colomé Monllao',
            'email' => 'jcolome@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'J.Luís',
            'sn1' => 'Colomé',
            'sn2' => 'Monllao',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('606')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '036',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '036'
            ]));

        User::createIfNotExists([
            'name' => 'Angel Portillo Lucas',
            'email' => 'angelportillo@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Angel',
            'sn1' => 'Portillo',
            'sn2' => 'Lucas',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('606')->id,
                'family_id' => Family::findByCode('ELECTRIC')->id,
                'code' => '037',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '037'
            ]));

        User::createIfNotExists([
            'name' => 'Anna Valls Montagut',
            'email' => 'avalls@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Anna',
            'sn1' => 'Valls',
            'sn2' => 'Montagut',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('517')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '064',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '064'
            ]));

        User::createIfNotExists([
            'name' => 'Anna Benaiges Bertomeu',
            'email' => 'anabenaiges@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Anna',
            'sn1' => 'Benaiges',
            'sn2' => 'Bertomeu',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('517')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '065',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '065'
            ]));

        User::createIfNotExists([
            'name' => 'Salomé Figueres Brescolí',
            'email' => 'salomefigueres@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Salomé',
            'sn1' => 'Figueres',
            'sn2' => 'Brescolí',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('517')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '067',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '067'
            ]));

        User::createIfNotExists([
            'name' => 'Pepa Cugat Tomàs',
            'email' => 'pepacugat@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Pepa',
            'sn1' => 'Cugat',
            'sn2' => 'Tomàs',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('517')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '066',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '066'
            ]));

        User::createIfNotExists([
            'name' => 'Berta Safont Recatalà',
            'email' => 'bertasafont@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Berta',
            'sn1' => 'Safont',
            'sn2' => 'Recatalà',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('518')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '062',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '062'
            ]));

        User::createIfNotExists([
            'name' => 'MªJesús Sales Berire',
            'email' => 'msales@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Mª Jesús',
            'sn1' => 'Sales',
            'sn2' => 'Berire',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('518')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '060',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '060'
            ]));

        User::createIfNotExists([
            'name' => 'MªLuisa Asensi Moltalva',
            'email' => 'mariaasensi@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Mª Jesús',
            'sn1' => 'Asensi',
            'sn2' => 'Moltalva',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('518')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '061',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '061'
            ]));

        User::createIfNotExists([
            'name' => 'Santi López Garcia',
            'email' => 'santiagolopez@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Santi',
            'sn1' => 'López',
            'sn2' => 'Garcia',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('518')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '063',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '063'
            ]));

        User::createIfNotExists([
            'name' => 'Lluis Ventura Forner',
            'email' => 'lventura@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Lluis',
            'sn1' => 'Ventura',
            'sn2' => 'Forner',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('619')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '069',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '069'
            ]));

        User::createIfNotExists([
            'name' => 'J.Antoni Pons Albalat',
            'email' => 'jpons@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'J.Antoni',
            'sn1' => 'Pons',
            'sn2' => 'Albalat',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('619')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '070',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '070'
            ]));

        User::createIfNotExists([
            'name' => 'Alicia Fàbrega Martínez',
            'email' => 'aliciafabrega@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Alicia',
            'sn1' => 'Fàbrega',
            'sn2' => 'Martínez',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('619')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '071',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '071'
            ]));

        User::createIfNotExists([
            'name' => 'Segis Benabent Gil',
            'email' => 'sbenabent@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Segis',
            'sn1' => 'Benabent',
            'sn2' => 'Gil',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('619')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '072',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '072'
            ]));

        User::createIfNotExists([
            'name' => 'Sandra Salvador Jovaní',
            'email' => 'sandrasalvador@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Sandra',
            'sn1' => 'Salvador',
            'sn2' => 'Jovaní',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('619')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '068',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '068'
            ]));

        User::createIfNotExists([
            'name' => 'MªJosé Caballé Valverde',
            'email' => 'mcaballe@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'MªJosé',
            'sn1' => 'Caballé',
            'sn2' => 'Valverde',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('620')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '074',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '074'
            ]));

        User::createIfNotExists([
            'name' => 'Marisa Ramón Pérez',
            'email' => 'mramon@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Marisa',
            'sn1' => 'Ramón',
            'sn2' => 'Pérez',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('620')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '073',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '073'
            ]));

        User::createIfNotExists([
            'name' => 'Elisa Puig Moll',
            'email' => 'epuig@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Elisa',
            'sn1' => 'Puig',
            'sn2' => 'Moll',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('620')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '075',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '075'
            ]));

        User::createIfNotExists([
            'name' => 'Ruth Hidalgo Vilar',
            'email' => 'rhidalgo@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Ruth',
            'sn1' => 'Hidalgo',
            'sn2' => 'Vilar',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('620')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '076',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '076'
            ]));

        User::createIfNotExists([
            'name' => 'Anna Sambartolomé Sancho',
            'email' => 'annasambartolome@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Anna',
            'sn1' => 'Sambartolomé',
            'sn2' => 'Sancho',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('620')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '077',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '077'
            ]));

        User::createIfNotExists([
            'name' => 'Cinta Mestre Escrihuela',
            'email' => 'cintamestre@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Cinta',
            'sn1' => 'Mestre',
            'sn2' => 'Escrihuela',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('620')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '078',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '078'
            ]));

        User::createIfNotExists([
            'name' => 'Trini Tomas Forcadell',
            'email' => 'trinidadtomas@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Trini',
            'sn1' => 'Tomas',
            'sn2' => 'Forcadell',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('620')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '080',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '080'
            ]));

        User::createIfNotExists([
            'name' => 'Adonay Pérez López',
            'email' => 'aperez@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Adonay',
            'sn1' => 'Pérez',
            'sn2' => 'López',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('620')->id,
                'family_id' => Family::findByCode('SANITAT')->id,
                'code' => '081',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '081'
            ]));

        User::createIfNotExists([
            'name' => 'Tarsi Royo Cruselles',
            'email' => 'troyo@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Tarsi',
            'sn1' => 'Royo',
            'sn2' => 'Cruselles',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('508')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '082',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '082'
            ]));

        User::createIfNotExists([
            'name' => 'Iris Maturana Andreu',
            'email' => 'irismaturana@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Iris',
            'sn1' => 'Maturana',
            'sn2' => 'Andreu',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('508')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '084',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '084'
            ]));

        User::createIfNotExists([
            'name' => 'Llatzer Cabó Bertomeu',
            'email' => 'llatzercarbo@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Llatzer',
            'sn1' => 'Cabó',
            'sn2' => 'Bertomeu',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('508')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '085',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '085'
            ]));

        User::createIfNotExists([
            'name' => 'Mercè Gilo Ortiz',
            'email' => 'mercegilo@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Mercè',
            'sn1' => 'Gilo',
            'sn2' => 'Ortiz',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('508')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '086',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '086'
            ]));

        User::createIfNotExists([
            'name' => 'Cristina Cardona Romero',
            'email' => 'ccardona99@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Cristina',
            'sn1' => 'Cardona',
            'sn2' => 'Romero',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('625')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '087',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '087'
            ]));

        User::createIfNotExists([
            'name' => 'David Gàmez Balaguer',
            'email' => 'dgamez1@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'David',
            'sn1' => 'Gàmez',
            'sn2' => 'Balaguer',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('625')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '088',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '088'
            ]));

        User::createIfNotExists([
            'name' => 'Àngels Garrido Borja',
            'email' => 'mgarrido2@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Àngels',
            'sn1' => 'Garrido',
            'sn2' => 'Borja',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('625')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '089',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '089'
            ]));

        User::createIfNotExists([
            'name' => 'Alicia Gamundi Vilà',
            'email' => 'aliciagamundi@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Alicia',
            'sn1' => 'Gamundi',
            'sn2' => 'Vilà',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('625')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '090',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '090'
            ]));

        User::createIfNotExists([
            'name' => 'Ricard Gonzalez Castelló',
            'email' => 'rgonzalez1@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Ricard',
            'sn1' => 'Gonzalez',
            'sn2' => 'Castelló',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('625')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '091',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '091'
            ]));

        User::createIfNotExists([
            'name' => 'Elena Mauri Cuenca',
            'email' => 'elenamauri@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Elena',
            'sn1' => 'Mauri',
            'sn2' => 'Cuenca',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('625')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '092',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '092'
            ]));

        User::createIfNotExists([
            'name' => 'Irene Alegre Chavarria',
            'email' => 'irenealegre@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Irene',
            'sn1' => 'Alegre',
            'sn2' => 'Chavarria',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('625')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '093',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '093'
            ]));

        User::createIfNotExists([
            'name' => 'Maria Castells Gilabert',
            'email' => 'mariacastells1@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Irene',
            'sn1' => 'Alegre',
            'sn2' => 'Chavarria',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('625')->id,
                'family_id' => Family::findByCode('SERVEIS')->id,
                'code' => '108',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '108'
            ]));

        User::createIfNotExists([
            'name' => 'Oscar Samo Franch',
            'email' => 'oscarsamo@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Oscar',
            'sn1' => 'Samo',
            'sn2' => 'Franch',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('501')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '014',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '014'
            ]));

        User::createIfNotExists([
            'name' => 'Enric Garcia Carcelén',
            'email' => 'egarci@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Enric',
            'sn1' => 'Garcia',
            'sn2' => 'Carcelén',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('501')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '015',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '015'
            ]));

        User::createIfNotExists([
            'name' => 'Eduard Ralda Simó',
            'email' => 'eralda@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Eduard',
            'sn1' => 'Ralda',
            'sn2' => 'Simó',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('501')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '016',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '016'
            ]));

        User::createIfNotExists([
            'name' => 'Pili Nuez Garcia',
            'email' => 'mnuez@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Pili',
            'sn1' => 'Nuez',
            'sn2' => 'Garcia',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('501')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '017',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '017'
            ]));

        User::createIfNotExists([
            'name' => 'MªRosa Ubalde Bellot',
            'email' => 'mariarosaubalde@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'MªRosa',
            'sn1' => 'Ubalde',
            'sn2' => 'Bellot',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('501')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '018',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '018'
            ]));

        User::createIfNotExists([
            'name' => 'Paqui Pinyol Moreso',
            'email' => 'fpinyol@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Paqui',
            'sn1' => 'Pinyol',
            'sn2' => 'Moreso',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('622')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '019',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '019'
            ]));

        User::createIfNotExists([
            'name' => 'Dolors Subirats Fabra',
            'email' => 'dsubirats@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Dolors',
            'sn1' => 'Subirats',
            'sn2' => 'Fabra',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('622')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '020',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '020'
            ]));

        User::createIfNotExists([
            'name' => 'Ferran Sabaté Borras',
            'email' => 'fsabate@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Ferran',
            'sn1' => 'Sabaté',
            'sn2' => 'Borras',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('622')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '021',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '021'
            ]));

        User::createIfNotExists([
            'name' => 'Araceli Esteller Hierro',
            'email' => 'aesteller@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Araceli',
            'sn1' => 'Esteller',
            'sn2' => 'Hierro',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('622')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '022',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '022'
            ]));

        User::createIfNotExists([
            'name' => 'Mavi Santamaria Andreu',
            'email' => 'mavisantamaria@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Mavi',
            'sn1' => 'Santamaria',
            'sn2' => 'Andreu',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('622')->id,
                'family_id' => Family::findByCode('ADMIN')->id,
                'code' => '023',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '023'
            ]));

        User::createIfNotExists([
            'name' => 'Agustí Moreso Garcia',
            'email' => 'amoreso@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Agustí',
            'sn1' => 'Moreso',
            'sn2' => 'Garcia',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('510')->id,
                'family_id' => Family::findByCode('COMERÇ')->id,
                'code' => '024',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '024'
            ]));

        User::createIfNotExists([
            'name' => 'Carme Vega Guerra',
            'email' => 'cvega@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Carme',
            'sn1' => 'Vega',
            'sn2' => 'Guerra',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('510')->id,
                'family_id' => Family::findByCode('COMERÇ')->id,
                'code' => '025',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '025'
            ]));

        User::createIfNotExists([
            'name' => 'Dolors Ferreres Gasulla',
            'email' => 'dolorsferreres@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Dolors',
            'sn1' => 'Ferreres',
            'sn2' => 'Gasulla',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('510')->id,
                'family_id' => Family::findByCode('COMERÇ')->id,
                'code' => '106',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '106'
            ]));

        User::createIfNotExists([
            'name' => 'Juan Abad Bueno',
            'email' => 'juandediosabad@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Juan',
            'sn1' => 'Abad',
            'sn2' => 'Bueno',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('510')->id,
                'family_id' => Family::findByCode('COMERÇ')->id,
                'code' => '107',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '107'
            ]));

        User::createIfNotExists([
            'name' => 'Just Pérez Santiago',
            'email' => 'justperez@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Just',
            'sn1' => 'Pérez',
            'sn2' => 'Santiago',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('621')->id,
                'family_id' => Family::findByCode('COMERÇ')->id,
                'code' => '026',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '026'
            ]));

        User::createIfNotExists([
            'name' => 'Armand Pons Roda',
            'email' => 'apons@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Armand',
            'sn1' => 'Pons',
            'sn2' => 'Roda',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('621')->id,
                'family_id' => Family::findByCode('COMERÇ')->id,
                'code' => '027',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '027'
            ]));

        User::createIfNotExists([
            'name' => 'Raquel Planell Tolos',
            'email' => 'raquelplanell@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Armand',
            'sn1' => 'Pons',
            'sn2' => 'Roda',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('621')->id,
                'family_id' => Family::findByCode('COMERÇ')->id,
                'code' => '105',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '105'
            ]));

        User::createIfNotExists([
            'name' => 'Marta Grau Ferrer',
            'email' => 'martagrau@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Marta',
            'sn1' => 'Grau',
            'sn2' => 'Ferrer',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('522')->id,
                'family_id' => Family::findByCode('ARTS')->id,
                'code' => '094',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '094'
            ]));

        User::createIfNotExists([
            'name' => 'Gerard Domenech Vendrell',
            'email' => 'gerarddomenech@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Gerard',
            'sn1' => 'Domenech',
            'sn2' => 'Vendrell',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('623')->id,
                'family_id' => Family::findByCode('ARTS')->id,
                'code' => '095',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '095'
            ]));

        User::createIfNotExists([
            'name' => 'J.Antonio Fernández Herraez',
            'email' => 'joseantoniofernandez1@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'J.Antonio',
            'sn1' => 'Fernández',
            'sn2' => 'Herraez',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('623')->id,
                'family_id' => Family::findByCode('ARTS')->id,
                'code' => '096',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '096'
            ]));

        User::createIfNotExists([
            'name' => 'Monica Moreno Dionis',
            'email' => 'monicamoreno@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Monica',
            'sn1' => 'Moreno',
            'sn2' => 'Dionis',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('621')->id,
                'family_id' => Family::findByCode('ARTS')->id,
                'code' => '097',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '097'
            ]));

        User::createIfNotExists([
            'name' => 'Santi Sabaté Sanz',
            'email' => 'ssabate@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Santi',
            'sn1' => 'Sabaté',
            'sn2' => 'Sanz',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('507')->id,
                'family_id' => Family::findByCode('INF')->id,
                'code' => '038',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '038'
            ]));

        User::createIfNotExists([
            'name' => 'Jordi Varas Aliau',
            'email' => 'jvaras@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Jordi',
            'sn1' => 'Varas',
            'sn2' => 'Aliau',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('507')->id,
                'family_id' => Family::findByCode('INF')->id,
                'code' => '039',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '039'
            ]));

        User::createIfNotExists([
            'name' => 'Sergi Tur Badenas',
            'email' => 'stur@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Sergi',
            'sn1' => 'Tur',
            'sn2' => 'Badenas',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('507')->id,
                'family_id' => Family::findByCode('INF')->id,
                'code' => '040',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '040'
            ]));

        User::createIfNotExists([
            'name' => 'Jaume Ramos Prades',
            'email' => 'jaumeramos@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Jaume',
            'sn1' => 'Ramos',
            'sn2' => 'Prades',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('507')->id,
                'family_id' => Family::findByCode('INF')->id,
                'code' => '041',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '041'
            ]));

        User::createIfNotExists([
            'name' => 'Quique Lorente Fuertes',
            'email' => 'quiquelorente@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Quique',
            'sn1' => 'Lorente',
            'sn2' => 'Fuertes',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('507')->id,
                'family_id' => Family::findByCode('INF')->id,
                'code' => '046',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '046'
            ]));

        User::createIfNotExists([
            'name' => 'A.Gonzal Verge Arnau',
            'email' => 'goncalverge@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'A.Gonzalb',
            'sn1' => 'Verge',
            'sn2' => 'Arnau',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('507')->id,
                'family_id' => Family::findByCode('INF')->id,
                'code' => '117',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '117'
            ]));

        User::createIfNotExists([
            'name' => 'Mireia Consarnau Pallarés',
            'email' => 'mireiaconsarnau@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Mireia',
            'sn1' => 'Consarnau',
            'sn2' => 'Pallarés',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('627')->id,
                'family_id' => Family::findByCode('INF')->id,
                'code' => '042',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '042'
            ]));

        User::createIfNotExists([
            'name' => 'Manel Macías Valanzuela',
            'email' => 'manelmacias@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Manel',
            'sn1' => 'Macías',
            'sn2' => 'Valanzuela',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('627')->id,
                'family_id' => Family::findByCode('INF')->id,
                'code' => '043',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '043'
            ]));

        User::createIfNotExists([
            'name' => 'Josep Dieg Cervellera Forcadell',
            'email' => 'josediegocervellera@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Josep Dieg0',
            'sn1' => 'Cervellera',
            'sn2' => 'Forcadell',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('627')->id,
                'family_id' => Family::findByCode('INF')->id,
                'code' => '045',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '045'
            ]));

        User::createIfNotExists([
            'name' => 'J.Luis Calderon Furió',
            'email' => 'jcaldero@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'J.Luis',
            'sn1' => 'Calderon',
            'sn2' => 'Furió',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('512')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '051',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '051'
            ]));

        User::createIfNotExists([
            'name' => 'Salvador Jareño Gas',
            'email' => 'sjareno@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Salvador',
            'sn1' => 'Jareño',
            'sn2' => 'Gas',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('512')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '052',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '052'
            ]));

        User::createIfNotExists([
            'name' => 'Jordi Brau Marza',
            'email' => 'jordibrau@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Jordi',
            'sn1' => 'Brau',
            'sn2' => 'Marza',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('512')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '053',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '053'
            ]));

        User::createIfNotExists([
            'name' => 'Joan Tiron Ferré',
            'email' => 'jtiron@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Joan',
            'sn1' => 'Tiron',
            'sn2' => 'Ferré',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('611')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '054',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '054'
            ]));

        User::createIfNotExists([
            'name' => 'Ricard Fernandez Burato',
            'email' => 'rfernand@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Ricard',
            'sn1' => 'Fernandez',
            'sn2' => 'Burato',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('611')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '055',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '055'
            ]));

        User::createIfNotExists([
            'name' => 'Ubaldo Arroyo Martínez',
            'email' => 'ubaldoarroyo@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Ubaldo',
            'sn1' => 'Arroyo',
            'sn2' => 'Martínez',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('611')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '056',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '056'
            ]));

        User::createIfNotExists([
            'name' => 'Fernando Segura Venezia',
            'email' => 'fernandosegura@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Fernando',
            'sn1' => 'Segura',
            'sn2' => 'Venezia',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('611')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '057',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '057'
            ]));

        User::createIfNotExists([
            'name' => 'Francesc Besalduch Piñol',
            'email' => 'sbesalduch@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Francesc',
            'sn1' => 'Besalduch',
            'sn2' => 'Piñol',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('611')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '058',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '058'
            ]));

        User::createIfNotExists([
            'name' => 'Manel Segarra Capera',
            'email' => 'msegarra@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Manel',
            'sn1' => 'Segarra',
            'sn2' => 'Capera',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('611')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '059',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '059'
            ]));

        User::createIfNotExists([
            'name' => 'Rosendo Ferri Marzo',
            'email' => 'rosendoferri@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Rosendo',
            'sn1' => 'Ferri',
            'sn2' => 'Marzo',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('611')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '049',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '049'
            ]));

        User::createIfNotExists([
            'name' => 'Jordi Sanchez Bel',
            'email' => 'jordisanchez@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Jordi',
            'sn1' => 'Sanchez',
            'sn2' => 'Bel',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('611')->id,
                'family_id' => Family::findByCode('FABRIC')->id,
                'code' => '050',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '050'
            ]));

        User::createIfNotExists([
            'name' => 'Albert Rofí Estelles',
            'email' => 'arofin@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Albert',
            'sn1' => 'Rofí',
            'sn2' => 'Estelles',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('504')->id,
                'family_id' => Family::findByCode('EDIFIC')->id,
                'code' => '047',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '047'
            ]));

        User::createIfNotExists([
            'name' => 'Pedro Guerrero López',
            'email' => 'pedroguerrero@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Pedro',
            'sn1' => 'Guerrero',
            'sn2' => 'López',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('612')->id,
                'family_id' => Family::findByCode('EDIFIC')->id,
                'code' => '048',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '048'
            ]));

        User::createIfNotExists([
            'name' => 'Teresa Lasala Descarrega',
            'email' => 'tlasala@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Teresa',
            'sn1' => 'Lasala',
            'sn2' => 'Descarrega',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('505')->id,
                'family_id' => Family::findByCode('FOL')->id,
                'code' => '009',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '009'
            ]));

        User::createIfNotExists([
            'name' => 'Carmina Andreu Pons',
            'email' => 'candreu@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Carmina',
            'sn1' => 'Andreu',
            'sn2' => 'Pons',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('505')->id,
                'family_id' => Family::findByCode('FOL')->id,
                'code' => '010',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '010'
            ]));

        User::createIfNotExists([
            'name' => 'J.Andrés Brocal Safont',
            'email' => 'jbrocal@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'J.Andrés',
            'sn1' => 'Brocal',
            'sn2' => 'Safont',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('505')->id,
                'family_id' => Family::findByCode('FOL')->id,
                'code' => '011',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '011'
            ]));

        User::createIfNotExists([
            'name' => 'Pilar Fadurdo Estrada',
            'email' => 'pilarfadurdo@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Pilar',
            'sn1' => 'Fadurdo',
            'sn2' => 'Estrada',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('505')->id,
                'family_id' => Family::findByCode('FOL')->id,
                'code' => '012',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '012'
            ]));

        User::createIfNotExists([
            'name' => 'Carlos Querol Bel',
            'email' => 'carlosquerol@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Carlos',
            'sn1' => 'Querol',
            'sn2' => 'Bel',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('505')->id,
                'family_id' => Family::findByCode('FOL')->id,
                'code' => '013',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '013'
            ]));

        User::createIfNotExists([
            'name' => 'Marisa Grau Campeón',
            'email' => 'cgrau@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Marisa',
            'sn1' => 'Grau',
            'sn2' => 'Campeón',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('505')->id,
                'family_id' => Family::findByCode('FOL')->id,
                'code' => '003',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '003'
            ]));

        User::createIfNotExists([
            'name' => 'Isabel Jordà Cabaces',
            'email' => 'ijorda@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Isabel',
            'sn1' => 'Jordà',
            'sn2' => 'Cabaces',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('AN')->id,
                'family_id' => Family::findByCode('CA')->id,
                'code' => '004',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '004'
            ]));

        User::createIfNotExists([
            'name' => 'Enric Querol Coll',
            'email' => 'equerol@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Enric',
            'sn1' => 'Querol',
            'sn2' => 'Coll',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('AN')->id,
                'family_id' => Family::findByCode('CA')->id,
                'code' => '005',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '005'
            ]));

        User::createIfNotExists([
            'name' => 'Lara Melich Cañado',
            'email' => 'laramelich@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Lara',
            'sn1' => 'Melich',
            'sn2' => 'Cañado',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('AN')->id,
                'family_id' => Family::findByCode('CA')->id,
                'code' => '006',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '006'
            ]));

        User::createIfNotExists([
            'name' => 'Carme Aznar Pedret',
            'email' => 'carmeaznar@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Carme',
            'sn1' => 'Aznar',
            'sn2' => 'Pedret',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('AN')->id,
                'family_id' => Family::findByCode('CA')->id,
                'code' => '007',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '007'
            ]));

        User::createIfNotExists([
            'name' => 'Julià Curto De la Vega',
            'email' => 'jcurto@iesebre.com',
            'password' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4', // secret
            'remember_token' => str_random(10),
        ])->addRole(Role::findByName('Teacher'))
        ->assignFullName([
            'givenName' => 'Julià',
            'sn1' => 'Curto',
            'sn2' => 'De la Vega',
        ])
        ->assignStaff(
            Staff::firstOrCreate([
                'type_id' => StaffType::findByName('Professor/a')->id,
                'specialty_id' => Specialty::findByCode('MA')->id,
                'family_id' => Family::findByCode('CA')->id,
                'code' => '008',
                'order' => 1
            ])
        )->assignTeacher(Teacher::firstOrCreate([
                'code' => '008'
            ]));
    }
}

if (!function_exists('initialize_families')) {
    function initialize_families()
    {
        Family::firstOrCreate([
            'name' => 'Sanitat',
            'code' => 'SANITAT'
        ]);

        Family::firstOrCreate([
            'name' => 'Informàtica',
            'code' => 'INF'
        ]);

        Family::firstOrCreate([
            'name' => 'Serveis socioculturals i a la comunitat',
            'code' => 'SERVEIS'
        ]);

        Family::firstOrCreate([
            'name' => 'Arts gràfiques',
            'code' => 'ARTS'
        ]);

      
        Family::firstOrCreate([
            'name' => 'Administració i finances',
            'code' => 'ADMIN'
        ]);

        Family::firstOrCreate([
            'name' => 'Comerç i marqueting',
            'code' => 'COMERÇ'
        ]);

        Family::firstOrCreate([
            'name' => 'Electricitat i electrònica/ Energia i aigua',
            'code' => 'ELECTRIC'
        ]);

        Family::firstOrCreate([
            'name' => 'Fabricació mecànica/ Instal·lació i manteniment',
            'code' => 'FABRIC'
        ]);

        Family::firstOrCreate([
            'name' => 'Edificació i obra civil',
            'code' => 'EDIFIC'
        ]);

        Family::firstOrCreate([
            'name' => 'Cursos d’accés',
            'code' => 'CA'
        ]);

        Family::firstOrCreate([
            'name' => 'Departament de llengües estrangeres',
            'code' => 'ESTRANGER'
        ]);

        Family::firstOrCreate([
            'name' => 'FOL',
            'code' => 'FOL'
        ]);
      
    }
}

if (!function_exists('initialize_forces')) {
    function initialize_forces()
    {
        Force::firstOrCreate([
            'name' => 'Mestres',
            'code' => 'MESTRES'
        ]);
        Force::firstOrCreate([
            'name' => "Professors d'ensenyament secundari",
            'code' => 'SECUNDARIA'
        ]);
        Force::firstOrCreate([
            'name' => 'Professors tècnics de formació professional',
            'code' => 'PT'
        ]);
        Force::firstOrCreate([
            'name' => "Professors d'escoles oficials d'idiomes",
            'code' => 'IDIOMES'
        ]);
    }
}


if (!function_exists('initialize_administrative_statuses')) {
    function initialize_administrative_statuses()
    {
        AdministrativeStatus::firstOrCreate([
            'name' => 'Funcionari/a amb plaça definitiva',
            'code' => 'FUNCIONARI DEF'
        ]);

        AdministrativeStatus::firstOrCreate([
            'name' => 'Funcionari/a propietari provisional',
            'code' => 'FUNCIONARI PROV'
        ]);

        AdministrativeStatus::firstOrCreate([
            'name' => 'Funcionari/a en pràctiques',
            'code' => 'FUNCIONARI PRAC'
        ]);

        AdministrativeStatus::firstOrCreate([
            'name' => 'Comissió de serveis',
            'code' => 'COMISSIÓ'
        ]);

        AdministrativeStatus::firstOrCreate([
            'name' => 'Interí/na',
            'code' => 'INTERÍ'
        ]);

        AdministrativeStatus::firstOrCreate([
            'name' => 'Substitut/a',
            'code' => 'SUBSTITUT'
        ]);

        AdministrativeStatus::firstOrCreate([
            'name' => 'Expert/a',
            'code' => 'EXPERT'
        ]);
    }
}

if (!function_exists('initialize_specialities')) {
    function initialize_specialities()
    {
        // Sanitat
        Specialty::firstOrCreate([
            'code' => '517',
            'name' => 'Processos diagnòstics clínics i productes ortoprotètics',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('SANITAT')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '518',
            'name' => 'Processos sanitaris',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('SANITAT')->id
        ]);

        // Cos -> Professors tècnics de formació professional
        Specialty::firstOrCreate([
            'code' => '619',
            'name' => 'Procediments de diagnòstic clínic i productes ortoprotètics',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('SANITAT')->id
        ]);

        // Cos -> Professors tècnics de formació professional
        Specialty::firstOrCreate([
            'code' => '620',
            'name' => 'Procediments sanitaris i assistencials ',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('SANITAT')->id
        ]);

        // Serveis socioculturals i a la comunitat
        Specialty::firstOrCreate([
            'code' => '508',
            'name' => 'Intervenció sociocomunitària',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('SERVEIS')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '625',
            'name' => 'Serveis a la comunitat',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('SERVEIS')->id
        ]);

        // Administració i finances
        Specialty::firstOrCreate([
            'code' => '501',
            'name' => 'Administració d’Empreses',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('ADMIN')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '622',
            'name' => 'Processos de Gestió Administrativa',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('ADMIN')->id
        ]);

        // Comerç i marqueting
        Specialty::firstOrCreate([
            'code' => '510',
            'name' => 'Organització i gestió comercial',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('ADMIN')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '621',
            'name' => 'Processos comercials',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('ADMIN')->id
        ]);

        // Informática
        Specialty::firstOrCreate([
            'code' => '507',
            'name' => 'Informàtica',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('INF')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '627',
            'name' => 'Sistemes i aplicacions informàtiques',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('INF')->id
        ]);

        // Electricitat i electrònica/ Energia i aigua
        Specialty::firstOrCreate([
            'code' => '524',
            'name' => 'Sistemes electrònics',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('ELECTRIC')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '525',
            'name' => 'Sistemes electrònics i automàtics',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('ELECTRIC')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '513',
            'name' => 'Organització i projectes de sistemes energètics',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('ELECTRIC')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '602',
            'name' => 'Equips electrònics',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('ELECTRIC')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '605',
            'name' => 'Instal·lació i manteniment d’equips tèrmics i de fluids',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('ELECTRIC')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '606',
            'name' => 'Instal·lacions electrotècniques',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('ELECTRIC')->id
        ]);

        // Fabricació mecànica/ Instal·lació i manteniment
        Specialty::firstOrCreate([
            'code' => '512',
            'name' => 'Organització i projectes de fabricació mecànica',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('FABRIC')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '611',
            'name' => 'Mecanització i manteniment de màquines',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('FABRIC')->id
        ]);

        // Arts gràfiques
        Specialty::firstOrCreate([
            'code' => '522',
            'name' => "Processos i productes d'arts gràfiques.",
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('ARTS')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '623',
            'name' => 'Producció en arts gràfiques',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('ARTS')->id
        ]);


        // Edificació i obra civil
        Specialty::firstOrCreate([
            'code' => '504',
            'name' => 'Construccions civils i edificació',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('EDIFIC')->id
        ]);

        Specialty::firstOrCreate([
            'code' => '612',
            'name' => 'Oficina de projectes de construcció',
            'force_id' => Force::findByCode('PT')->id,
            'family_id' => Family::findByCode('EDIFIC')->id
        ]);

        // Cursos d’accés
        // Cos -> Secundària
        Specialty::firstOrCreate([
            'code' => 'MA',
            'name' => 'Matemàtiques',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('CA')->id
        ]);

        Specialty::firstOrCreate([
            'code' => 'CAS',
            'name' => 'Castellà',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('CA')->id
        ]);

        // Departament de llengües estrangeres
        // Cos -> Secundària
        Specialty::firstOrCreate([
            'code' => 'AN',
            'name' => 'Àngles',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('ESTRANGER')->id
        ]);


        // FOL
        Specialty::firstOrCreate([
            'code' => '505',
            'name' => 'Formació i orientació laboral',
            'force_id' => Force::findByCode('SECUNDARIA')->id,
            'family_id' => Family::findByCode('FOL')->id
        ]);

    }
}



if (!function_exists('initialize_user_types')) {
    function initialize_user_types()
    {
        $teacher = UserType::firstOrCreate([
            'name' => 'Professor/a'
        ]);
        $teacher->roles()->save(Role::findByName('Teacher'));

        $student = UserType::firstOrCreate([
            'name' => 'Alumne/a'
        ]);
        $student->roles()->save(Role::findByName('Student'));

        UserType::firstOrCreate([
            'name' => 'Conserge'
        ]);

        UserType::firstOrCreate([
            'name' => 'Administratiu/va'
        ]);

        UserType::firstOrCreate([
            'name' => 'Familiars'
        ]);
    }
}

if (!function_exists('apply_tenant')) {
    function apply_tenant($name)
    {
        if ($tenant = get_tenant($name)) {
            $tenant->connect();
            $tenant->configure();
            Config::set('database.default', 'tenant');
        } else {
            dump('Tenant not found!');
        }
    }
}

if (!function_exists('add_fake_pending_teacher')) {
    function add_fake_pending_teacher()
    {
        return PendingTeacher::create([
            'name' => 'Pepe',
            'sn1' => 'Pardo',
            'sn2' => 'Jeans',
            'identifier' => '84008343S',
            'birthdate' => '1980-02-04',
            'street' => 'Alcanyiz',
            'number' => 40,
            'floor' => 3,
            'floor_number' => 1,
            'postal_code' => 43500,
            'locality' => 'TORTOSA',
            'province' => 'TARRAGONA',
            'email' => 'pepe@pardo.com',
            'other_emails' => 'pepepardojeans@gmail.com,ppardo@xtec.cat',
            'telephone' => '679852467',
            'other_telephones' => '977854265,689578458',
            'degree' => 'Enginyer en chapuzas varias',
            'other_degrees' => 'Master emerito por la Juan Carlos Primero',
            'languages' => 'Suajili',
            'profiles' => 'Master of the Universe',
            'other_training' => 'Fuster',
            'force_id' => 1,
            'specialty_id' => 1,
            'teacher_start_date' => '2015',
            'start_date' => '2017-03-06',
            'opositions_date' => '2009-06-10',
            'administrative_status_id' => 1,
            'destination_place' => 'La Seu Urgell',
            'teacher_id' => 1
        ]);
    }
}

if (! function_exists('seed_states')) {
    function seed_states()
    {

        DB::table('states')->delete();


        // Taken from //https://gist.github.com/daguilarm/0e93b73779f0306e5df2
        DB::table('states')->insert([
            ['id' => '1', 'country_code' => "ESP", 'name' => "Andalucía", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '2', 'country_code' => "ESP", 'name' => "Aragón", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '3', 'country_code' => "ESP", 'name' => "Asturias, Principado de", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '4', 'country_code' => "ESP", 'name' => "Baleares, Islas", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '5', 'country_code' => "ESP", 'name' => "Canarias", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '6', 'country_code' => "ESP", 'name' => "Cantabria", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '7', 'country_code' => "ESP", 'name' => "Castilla y León", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '8', 'country_code' => "ESP", 'name' => "Castilla - La Mancha", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '9', 'country_code' => "ESP", 'name' => "Cataluña", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '10', 'country_code' => "ESP", 'name' => "Comunidad Valenciana", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '11', 'country_code' => "ESP", 'name' => "Extremadura", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '12', 'country_code' => "ESP", 'name' => "Galicia", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '13', 'country_code' => "ESP", 'name' => "Madrid, Comunidad de", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '14', 'country_code' => "ESP", 'name' => "Murcia, Región de", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '15', 'country_code' => "ESP", 'name' => "Navarra, Comunidad Foral de", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '16', 'country_code' => "ESP", 'name' => "País Vasco", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '17', 'country_code' => "ESP", 'name' => "Rioja, La", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '18', 'country_code' => "ESP", 'name' => "Ceuta", 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '19', 'country_code' => "ESP", 'name' => "Melilla", 'created_at' => new DateTime, 'updated_at' => new DateTime]
        ]);
    }
}

if (! function_exists('seed_provinces')) {
    function seed_provinces()
    {
        seed_states();

        DB::table('provinces')->delete();


        // Taken from //https://gist.github.com/daguilarm/0e93b73779f0306e5df2
        DB::table('provinces')->insert([
            ['id' => '1','state_id' => 8, 'postal_code_prefix' => '02' , 'name' => 'Albacete', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '2','state_id' => 8, 'postal_code_prefix' => '13' , 'name' => 'Ciudad Real', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '3','state_id' => 8, 'postal_code_prefix' => '16' , 'name' => 'Cuenca', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '4','state_id' => 8, 'postal_code_prefix' => '19' , 'name' => 'Guadalajara', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '5','state_id' => 8, 'postal_code_prefix' => '45' , 'name' => 'Toledo', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '6','state_id' => 2, 'postal_code_prefix' => '22' , 'name' => 'Huesca', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '7','state_id' => 2, 'postal_code_prefix' => '44' , 'name' => 'Teruel', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '8','state_id' => 2, 'postal_code_prefix' => '50' , 'name' => 'Zaragoza', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '9','state_id' => 18, 'postal_code_prefix' => '51' , 'name' => 'Ceuta', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '10','state_id' => 13, 'postal_code_prefix' => '28' , 'name' => 'Madrid', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '11','state_id' => 14, 'postal_code_prefix' => '30' , 'name' => 'Murcia', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '12','state_id' => 19, 'postal_code_prefix' => '52' , 'name' => 'Melilla', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '13','state_id' => 15, 'postal_code_prefix' => '31' , 'name' => 'Navarra', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '14','state_id' => 1, 'postal_code_prefix' => '04' , 'name' => 'Almería', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '15','state_id' => 1, 'postal_code_prefix' => '11' , 'name' => 'Cádiz', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '16','state_id' => 1, 'postal_code_prefix' => '14' , 'name' => 'Córdoba', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '17','state_id' => 1, 'postal_code_prefix' => '18' , 'name' => 'Granada', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '18','state_id' => 1, 'postal_code_prefix' => '21' , 'name' => 'Huelva', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '19','state_id' => 1, 'postal_code_prefix' => '23' , 'name' => 'Jaén', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '20','state_id' => 1, 'postal_code_prefix' => '29' , 'name' => 'Málaga', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '21','state_id' => 1, 'postal_code_prefix' => '41' , 'name' => 'Sevilla', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '22','state_id' => 3, 'postal_code_prefix' => '33' , 'name' => 'Asturias', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '23','state_id' => 6, 'postal_code_prefix' => '39' , 'name' => 'Cantabria', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '24','state_id' => 7, 'postal_code_prefix' => '05' , 'name' => 'Ávila', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '25','state_id' => 7, 'postal_code_prefix' => '09' , 'name' => 'Burgos', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '26','state_id' => 7, 'postal_code_prefix' => '24' , 'name' => 'León', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '27','state_id' => 7, 'postal_code_prefix' => '34' , 'name' => 'Palencia', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '28','state_id' => 7, 'postal_code_prefix' => '37' , 'name' => 'Salamanca', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '29','state_id' => 7, 'postal_code_prefix' => '40' , 'name' => 'Segovia', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '30','state_id' => 7, 'postal_code_prefix' => '42' , 'name' => 'Soria', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '31','state_id' => 7, 'postal_code_prefix' => '47' , 'name' => 'Valladolid', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '32','state_id' => 7, 'postal_code_prefix' => '49' , 'name' => 'Zamora', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '33','state_id' => 9, 'postal_code_prefix' => '08' , 'name' => 'Barcelona', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '34','state_id' => 9, 'postal_code_prefix' => '17' , 'name' => 'Girona', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '35','state_id' => 9, 'postal_code_prefix' => '25' , 'name' => 'Lleida', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '36','state_id' => 9, 'postal_code_prefix' => '43' , 'name' => 'Tarragona', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '37','state_id' => 11, 'postal_code_prefix' => '06' , 'name' => 'Badajoz', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '38','state_id' => 11, 'postal_code_prefix' => '10' , 'name' => 'Cáceres', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '39','state_id' => 12, 'postal_code_prefix' => '15' , 'name' => 'Coruña, La', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '40','state_id' => 12, 'postal_code_prefix' => '27' , 'name' => 'Lugo', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '41','state_id' => 12, 'postal_code_prefix' => '32' , 'name' => 'Orense', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '42','state_id' => 12, 'postal_code_prefix' => '36' , 'name' => 'Pontevedra', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '43','state_id' => 17, 'postal_code_prefix' => '26' , 'name' => 'Rioja, La', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '44','state_id' => 4, 'postal_code_prefix' => '07' , 'name' => 'Baleares, Islas', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '45','state_id' => 16, 'postal_code_prefix' => '01' , 'name' => 'Álava', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '46','state_id' => 16, 'postal_code_prefix' => '20' , 'name' => 'Guipúzcoa', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '47','state_id' => 16, 'postal_code_prefix' => '48' , 'name' => 'Vizcaya', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '48','state_id' => 5, 'postal_code_prefix' => '35' , 'name' => 'Palmas, Las', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '49','state_id' => 5, 'postal_code_prefix' => '38' , 'name' => 'Tenerife, Santa Cruz De', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '50','state_id' => 10, 'postal_code_prefix' => '03' , 'name' => 'Alacant', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '51','state_id' => 10, 'postal_code_prefix' => '12' , 'name' => 'Castelló', 'created_at' => new DateTime, 'updated_at' => new DateTime],
            ['id' => '52','state_id' => 10, 'postal_code_prefix' => '46' , 'name' => 'Valencia', 'created_at' => new DateTime, 'updated_at' => new DateTime]
        ]);
    }
}

if (! function_exists('tune_google_client')) {
    function tune_google_client()
    {
        app()->extend(\PulkitJalan\Google\Client::class, function ($command, $app) {
            $config = $app['config']['google'];
            $user = 'sergitur@iesebre.com';
            return new Client($config, $user);
        });
    }
}

if (! function_exists('get_photo_slugs_from_path')) {
    /**
     * Get photos slugs from path.
     *
     * @param $path
     * @return \Illuminate\Support\Collection|static
     */
    function get_photo_slugs_from_path($path)
    {
        $photos = collect();
        if (Storage::exists($path)) {
            $photos = collect(File::allFiles(Storage::path($path)))->map(function ($photo) {
                return [
                    'file' => $photo,
                    'filename' => $filename = $photo->getFilename(),
                    'slug' => str_slug($filename,'-')
                ];
            });
        }
        return $photos;
    }
}

if (! function_exists('first_or_create_identifier_type')) {
    /**
     * Create contact type if not exists and return new o already existing contact type.
     */
    function first_or_create_identifier_type($name)
    {
        try {
            return IdentifierType::create(['name' => $name]);
        } catch (Illuminate\Database\QueryException $e) {
            return IdentifierType::where('name', $name);
        }
    }
}

if (! function_exists('seed_identifier_types')) {
    /**
     * Create identifier types.
     */
    function seed_identifier_types()
    {
        first_or_create_identifier_type('NIF');
        first_or_create_identifier_type('Pasaporte');
        first_or_create_identifier_type('NIE');
        first_or_create_identifier_type('TIS');
    }
}