<?php
/**
 * Book routes. A route only reads the request, calls the service and returns JSON.
 * Validation and business rules are in BookService.
 */

/**
 * @OA\Get(
 *     path="/books",
 *     tags={"books"},
 *     summary="List books with author and category names",
 *     @OA\Parameter(name="search", in="query", required=false, description="Search by title or author name", @OA\Schema(type="string"), example="orwell"),
 *     @OA\Parameter(name="category_id", in="query", required=false, description="Only books in this category", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="author_id", in="query", required=false, description="Only books by this author", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="List of books", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Book")))
 * )
 */
Flight::route('GET /books', function () {
    $query = Flight::request()->query;
    Flight::json(Flight::bookService()->getAll([
        'search'      => $query['search'],
        'category_id' => $query['category_id'],
        'author_id'   => $query['author_id'],
    ]));
});

/**
 * @OA\Get(
 *     path="/books/{id}",
 *     tags={"books"},
 *     summary="Get one book with author, category and rating summary",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, description="The book", @OA\JsonContent(ref="#/components/schemas/BookDetails")),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /books/@id:[0-9]+', function ($id) {
    Flight::json(Flight::bookService()->getById((int) $id));
});

/**
 * @OA\Get(
 *     path="/books/{id}/reviews",
 *     tags={"books"},
 *     summary="Get the reviews of one book",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, description="Reviews of the book", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Review"))),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /books/@id:[0-9]+/reviews', function ($id) {
    Flight::json(Flight::reviewService()->getByBook((int) $id));
});

/**
 * @OA\Post(
 *     path="/books",
 *     tags={"books"},
 *     summary="Add a book",
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/BookInput")),
 *     @OA\Response(response=201, description="The created book", @OA\JsonContent(ref="#/components/schemas/BookDetails")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
 * )
 */
Flight::route('POST /books', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::bookService()->create($data), 201);
});

/**
 * @OA\Put(
 *     path="/books/{id}",
 *     tags={"books"},
 *     summary="Update a book (only the fields that are sent are changed)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/BookInput")),
 *     @OA\Response(response=200, description="The updated book", @OA\JsonContent(ref="#/components/schemas/BookDetails")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('PUT /books/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::bookService()->update((int) $id, $data));
});

/**
 * @OA\Delete(
 *     path="/books/{id}",
 *     tags={"books"},
 *     summary="Delete a book (not allowed while it has active loans)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, ref="#/components/responses/Deleted"),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('DELETE /books/@id:[0-9]+', function ($id) {
    Flight::bookService()->delete((int) $id);
    Flight::json(['message' => 'Book deleted successfully.']);
});
