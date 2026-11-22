<?php
/**
 * User routes (admin's Users page). Registration and login with JWT come in Milestone 4.
 * The password hash is never returned.
 */

/**
 * @OA\Get(
 *     path="/users",
 *     tags={"users"},
 *     summary="List users",
 *     @OA\Response(response=200, description="List of users", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User")))
 * )
 */
Flight::route('GET /users', function () {
    Flight::json(Flight::userService()->getAll());
});

/**
 * @OA\Get(
 *     path="/users/{id}",
 *     tags={"users"},
 *     summary="Get one user",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=2),
 *     @OA\Response(response=200, description="The user", @OA\JsonContent(ref="#/components/schemas/User")),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /users/@id:[0-9]+', function ($id) {
    Flight::json(Flight::userService()->getById((int) $id));
});

/**
 * @OA\Get(
 *     path="/users/{id}/loans",
 *     tags={"users"},
 *     summary="Get the loans of one user (My loans on the Profile page)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=2),
 *     @OA\Response(response=200, description="Loans of the user", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Loan"))),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /users/@id:[0-9]+/loans', function ($id) {
    Flight::json(Flight::loanService()->getByUser((int) $id));
});

/**
 * @OA\Post(
 *     path="/users",
 *     tags={"users"},
 *     summary="Add a user",
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserInput")),
 *     @OA\Response(response=201, description="The created user", @OA\JsonContent(ref="#/components/schemas/User")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
 * )
 */
Flight::route('POST /users', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::userService()->create($data), 201);
});

/**
 * @OA\Put(
 *     path="/users/{id}",
 *     tags={"users"},
 *     summary="Update a user (send password only to change it)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=2),
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserInput")),
 *     @OA\Response(response=200, description="The updated user", @OA\JsonContent(ref="#/components/schemas/User")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('PUT /users/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::userService()->update((int) $id, $data));
});

/**
 * @OA\Delete(
 *     path="/users/{id}",
 *     tags={"users"},
 *     summary="Delete a user (their loans and reviews are deleted too)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=4),
 *     @OA\Response(response=200, ref="#/components/responses/Deleted"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('DELETE /users/@id:[0-9]+', function ($id) {
    Flight::userService()->delete((int) $id);
    Flight::json(['message' => 'User deleted successfully.']);
});
