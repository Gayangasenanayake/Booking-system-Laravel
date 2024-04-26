<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Stancl\Tenancy\Middleware\IdentificationMiddleware;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

class ValidateTenantUser extends IdentificationMiddleware
{

    /** @var callable|null */
    public static $onFail;

    /** @var Tenancy */
    protected $tenancy;

    /** @var DomainTenantResolver */
    protected $resolver;

    public function __construct(Tenancy $tenancy, DomainTenantResolver $resolver)
    {
        $this->tenancy = $tenancy;
        $this->resolver = $resolver;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     * @throws TenantCouldNotBeIdentifiedException
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

//        $domain = $request->getHost();
//        $tenant = $this->resolver->resolve($domain);

        $this->tenancy->initialize($user->tenant_id);
        return $next($request);

//        if ($tenant && $user) {
//            if ($tenant->users()->where('user_id', $user->id)->exists()) {
//                $this->tenancy->initialize($tenant);
//                return $next($request);
//            } else {
//                auth()->user()->tokens()->delete();
//                return response()->json(['message' => 'Unauthorized access!'], 401);
//            }
//        } else {
//            return response()->json(['message' => 'Something went wrong!'], 401);
//        }
//        $this->tenancy->initialize($tenant);
//        if (DB::table('users')->where('email', $user->email)->exists()){
//            return $next($request);
//        } else {
//            return response()->json(['message' => 'Unauthorized access'], 401);
//        }
//        if ($user->tenant_id === $tenant->id) {
//            $this->tenancy->initialize($tenant);
//            return $next($request);
//        } else {
//            auth()->user()->tokens()->delete();
//            return response()->json(['message' => 'Unauthorized access!'], 401);
//        }
    }
}
