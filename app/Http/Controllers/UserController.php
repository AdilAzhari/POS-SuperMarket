<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\CreateUserDTOAction;
use App\Actions\Common\FormatApiResponseAction;
use App\Actions\Common\HandleControllerErrorsAction;
use App\Actions\Common\HandleValidatedRequestAction;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class UserController extends Controller
{
    public function __construct(
        private readonly HandleValidatedRequestAction $validationHandler,
        private readonly FormatApiResponseAction $responseFormatter,
        private readonly HandleControllerErrorsAction $errorHandler,
        private readonly CreateUserDTOAction $createUserDTOAction
    ) {}

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->validatePagination($request);
            $perPage = $validated['per_page'] ?? 20;

            $users = User::query()->select('id', 'name', 'email', 'created_at')
                ->paginate($perPage);

            return $this->responseFormatter->paginated($users);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'user listing');
        }
    }

    /**
     * Store a newly created user
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $userData = $this->validationHandler->execute($request);

            $userDTO = $this->createUserDTOAction->forUser($userData);

            $user = User::query()->create($userDTO->toCreateArray());

            return $this->responseFormatter->created(
                $user->only(['id', 'name', 'email', 'role', 'employee_id', 'created_at']),
                'User created successfully'
            );
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'user creation');
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        return $this->responseFormatter->resource(
            $user->only(['id', 'name', 'email', 'created_at'])
        );
    }

    /**
     * Update the specified user
     */
    public function update(StoreEmployeeRequest $request, User $user): JsonResponse
    {
        try {
            $data = $this->validationHandler->execute($request);

            $userDTO = $this->createUserDTOAction->fromArrayWithExisting($data, $user);

            if (isset($data['password'])) {
                $updateData = $userDTO->toUpdateArray();
                $updateData['password'] = Hash::make($data['password']);
            } else {
                $updateData = $userDTO->toUpdateArray();
            }

            $user->update($updateData);

            return $this->responseFormatter->updated(
                $user->only(['id', 'name', 'email', 'role', 'employee_id', 'is_active', 'created_at']),
                'User updated successfully'
            );
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'user update');
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $user->delete();

            return $this->responseFormatter->deleted('User deleted successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'user deletion');
        }
    }
}
