# JWT lumen authentication

### Require as composer dependency

add dependency to `composer.json`:
`"aschulhofer/woodstick-jwt-auth": "0.0.*"`

add repositories if not present:
```php
"repositories": [
    {
        "type": "vcs",
        "url":  "git@github.com:aschulhofer/woodstick-jwt-auth.git"
    },
    {
        "type": "vcs",
        "url":  "git@github.com:aschulhofer/woodstick-jwt.git"
    }
],

```

## Lumen

### Environment
Add to `.env`

```
AUTH_GUARD=axt-jwt
AUTH_AXT_JWT_SECRET=<secret>
```

Optionally add:
`AUTH_AXT_JWT_SIGNATURE` with possible values `Hmac`, `Rsa`, `Ecdsa`
`AUTH_AXT_JWT_ALGO` with possible values `Sha256`, `Sha384`, `Sha512`

### Config files

Create `axt-jwt.php` under `config`, see content of `config/axt-jwt.php`

Add `auth.php` under `config`:
```php
<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'api'),
    ],

    'guards' => [
        'api' => ['driver' => 'api'],
        'axt-jwt' => [
            'driver' => 'axt-jwt',
            'provider' => 'users',
        ]
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Data\Model\User::class,
        ],
    ],

    'passwords' => [
        //
    ],

];
```

### Bootstrap

Uncomment in `bootstrap/app.php`:

```php
...
$app->withFacades();
$app->withEloquent();
...
```

Add services provider  in `bootstrap/app.php`:
```php
$app->register(Woodstick\Providers\JWTAuthServiceProvider::class);
```


### Mysql
Create table for jwt tokens:

#### Create migration
`php artisan make:migration create_users_table --create=users`
`php artisan make:migration create_jwttokens_table --create=jwttokens`

#### Create seeder
`php artisan make:seeder UsersTableSeeder`

Do not rename created seeder. If you have to rename the seeder, create new one with correct name and copy the content of the old one, otherwise
`db:seed` is not finding the seeder class.

#### Model factory
Add following code to `ModelFactory.php` in directory `database/factories`:
```php
$factory->define(App\Data\Model\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = Illuminate\Support\Facades\Hash::make('secret'),
    ];
});
```

In file `database/seeds/DatabaseSeeder.php` add following line to `run` method:
```php
$this->call('UsersTableSeeder');
```

#### Run migration (after models and migrations code are created, see below)
run `php artisan migrate:install`
run `php artisan migrate`

#### Run seeder (after factory and seeder code are created, see below)
`php artisan db:seed`
`php artisan db:seed --class=UsersTableSeeder`


#### Migration/Model/Seeder Code

User model table migration:
```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    protected $tableName = 'users';
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}

```

JWT token table migration:
```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJwttokensTable extends Migration
{
    protected $tableName = 'jwttokens';
    protected $usersTableName = 'users';
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->string('token')->unique();
            $table->integer('user_id')->unsigned();
            $table->dateTime('issued_at');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_access')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on($this->usersTableName);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}

```

UsersTableSeeder:
```php
<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds. The used factory is defined in ModelFactory.php under database/factories.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Data\Model\User::class, 5)->create();
    }
}

```

### Model

Create eloquent user model class under `app/Data/Model` with namespace `App\Data\Model`:

```php
<?php

namespace App\Data\Model;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes as SoftDeletesTrait;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use SoftDeletesTrait, Authenticatable, Authorizable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];
}

```



#### Misc

Add login controller:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function login(Request $request) {

        $email = $request->input('email', 'default@default.de');
        $password = $request->input('password');

        $token = Auth::attempt(['email' => $email, 'password' => $password]);
        if($token) {
            return response()->json(
                [
                    'success' => true,
                    'email' => $email,
                    'token' => strval($token),
                ]
            );
        }
        else {
            return response()->json(['success' => false, 'email' => $email]);
        }
    }
}
```

Add logout controller:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Factory as Auth;
use function response;

class LogoutController extends Controller
{
    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     *
     */
    public function logout() {
        
        $this->auth->guard()->logout();

        return response()->json(
            [
                'success' => true
            ]
        );
    }
}

```


Add sample controller for protected route:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class WoodstickController extends Controller
{
    public function __construct() {
    }

    public function tokenTest(Request $request) {
        
        $user = Auth::user();
        
        return response()->json(
            [
                'token' => $request->header('Authorization'),
                'you' => $user->toArray(),
            ]
        );
    }
}
```

Add routes:
```php
$app->group(['prefix' => 'api'], function() use ($app) {
    $app->post('login', 'LoginController@login');
});

$app->group(['prefix' => 'api', 'middleware' => 'auth'], function () use ($app) {
    $app->get('tokenTest', 'WoodstickController@tokenTest');
    
    $app->post('logout', 'LogoutController@logout');
});
```


POST request to login to `api/login`:
With `Content-Type` header `application/json`:

```javascript
{
    "email": "hills.jana@example.org",
    "password": "secret"
}
```

returns JWT Token.


GET request to test authentication to `api/tokenTest`:
With jwt token prefixed with `Bearer ` in `Authorization` header

`Authorization: Bearer <jwt-token>`

POST request to logout to `api/logout`:
With jwt token prefixed with `Bearer ` in `Authorization` header

`Authorization: Bearer <jwt-token>`

