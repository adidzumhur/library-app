<?php

/**
 * @OA\Get(
 *     path="/reviews",
 *     tags={"reviews"},
 *     summary="List reviews with book title and reviewer name",
 *     @OA\Response(response=200, description="List of reviews", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Review")))
 * )
 */
Flight::route('GET /reviews', function () {
    Flight::json(Flight::reviewService()->getAll());
});

/**
 * @OA\Get(
 *     path="/reviews/{id}",
 *     tags={"reviews"},
 *     summary="Get one review",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, description="The review", @OA\JsonContent(ref="#/components/schemas/Review")),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /reviews/@id:[0-9]+', function ($id) {
    Flight::json(Flight::reviewService()->getById((int) $id));
});

/**
 * @OA\Post(
 *     path="/reviews",
 *     tags={"reviews"},
 *     summary="Add a review (one review per member and book)",
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ReviewInput")),
 *     @OA\Response(response=201, description="The created review", @OA\JsonContent(ref="#/components/schemas/Review")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
 * )
 */
Flight::route('POST /reviews', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::reviewService()->create($data), 201);
});

/**
 * @OA\Put(
 *     path="/reviews/{id}",
 *     tags={"reviews"},
 *     summary="Update the rating and/or comment of a review",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ReviewUpdate")),
 *     @OA\Response(response=200, description="The updated review", @OA\JsonContent(ref="#/components/schemas/Review")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('PUT /reviews/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::reviewService()->update((int) $id, $data));
});

/**
 * @OA\Delete(
 *     path="/reviews/{id}",
 *     tags={"reviews"},
 *     summary="Delete a review",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, ref="#/components/responses/Deleted"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('DELETE /reviews/@id:[0-9]+', function ($id) {
    Flight::reviewService()->delete((int) $id);
    Flight::json(['message' => 'Review deleted successfully.']);
});
