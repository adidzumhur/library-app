<?php

/**
 * @OA\Get(
 *     path="/categories",
 *     tags={"categories"},
 *     summary="List categories with their number of books",
 *     @OA\Response(response=200, description="List of categories", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Category")))
 * )
 */
Flight::route('GET /categories', function () {
    Flight::json(Flight::categoryService()->getAll());
});

/**
 * @OA\Get(
 *     path="/categories/{id}",
 *     tags={"categories"},
 *     summary="Get one category",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, description="The category", @OA\JsonContent(ref="#/components/schemas/Category")),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /categories/@id:[0-9]+', function ($id) {
    Flight::json(Flight::categoryService()->getById((int) $id));
});

/**
 * @OA\Get(
 *     path="/categories/{id}/books",
 *     tags={"categories"},
 *     summary="Get the books in one category",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, description="Books in the category", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Book"))),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /categories/@id:[0-9]+/books', function ($id) {
    Flight::json(Flight::categoryService()->getBooks((int) $id));
});

/**
 * @OA\Post(
 *     path="/categories",
 *     tags={"categories"},
 *     summary="Add a category",
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CategoryInput")),
 *     @OA\Response(response=201, description="The created category", @OA\JsonContent(ref="#/components/schemas/Category")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
 * )
 */
Flight::route('POST /categories', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::categoryService()->create($data), 201);
});

/**
 * @OA\Put(
 *     path="/categories/{id}",
 *     tags={"categories"},
 *     summary="Update a category",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CategoryInput")),
 *     @OA\Response(response=200, description="The updated category", @OA\JsonContent(ref="#/components/schemas/Category")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('PUT /categories/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::categoryService()->update((int) $id, $data));
});

/**
 * @OA\Delete(
 *     path="/categories/{id}",
 *     tags={"categories"},
 *     summary="Delete a category (its books stay in the library without a category)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, ref="#/components/responses/Deleted"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('DELETE /categories/@id:[0-9]+', function ($id) {
    Flight::categoryService()->delete((int) $id);
    Flight::json(['message' => 'Category deleted successfully.']);
});
