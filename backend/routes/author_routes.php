<?php

/**
 * @OA\Get(
 *     path="/authors",
 *     tags={"authors"},
 *     summary="List authors with their number of books",
 *     @OA\Parameter(name="search", in="query", required=false, description="Search by first or last name", @OA\Schema(type="string"), example="andri"),
 *     @OA\Response(response=200, description="List of authors", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Author")))
 * )
 */
Flight::route('GET /authors', function () {
    Flight::json(Flight::authorService()->getAll(Flight::request()->query['search']));
});

/**
 * @OA\Get(
 *     path="/authors/{id}",
 *     tags={"authors"},
 *     summary="Get one author",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, description="The author", @OA\JsonContent(ref="#/components/schemas/Author")),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /authors/@id:[0-9]+', function ($id) {
    Flight::json(Flight::authorService()->getById((int) $id));
});

/**
 * @OA\Get(
 *     path="/authors/{id}/books",
 *     tags={"authors"},
 *     summary="Get the books of one author",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, description="Books of the author", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Book"))),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /authors/@id:[0-9]+/books', function ($id) {
    Flight::json(Flight::authorService()->getBooks((int) $id));
});

/**
 * @OA\Post(
 *     path="/authors",
 *     tags={"authors"},
 *     summary="Add an author",
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/AuthorInput")),
 *     @OA\Response(response=201, description="The created author", @OA\JsonContent(ref="#/components/schemas/Author")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
 * )
 */
Flight::route('POST /authors', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::authorService()->create($data), 201);
});

/**
 * @OA\Put(
 *     path="/authors/{id}",
 *     tags={"authors"},
 *     summary="Update an author (only the fields that are sent are changed)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/AuthorInput")),
 *     @OA\Response(response=200, description="The updated author", @OA\JsonContent(ref="#/components/schemas/Author")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('PUT /authors/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::authorService()->update((int) $id, $data));
});

/**
 * @OA\Delete(
 *     path="/authors/{id}",
 *     tags={"authors"},
 *     summary="Delete an author (not allowed while the author has books)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, ref="#/components/responses/Deleted"),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('DELETE /authors/@id:[0-9]+', function ($id) {
    Flight::authorService()->delete((int) $id);
    Flight::json(['message' => 'Author deleted successfully.']);
});
