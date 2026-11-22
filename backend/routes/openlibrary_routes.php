<?php
/**
 * External API: Open Library. Used by the "Import by ISBN" feature on the Books page.
 * The frontend calls OUR backend, and the backend calls Open Library (OpenLibraryService).
 */

/**
 * @OA\Get(
 *     path="/openlibrary/isbn/{isbn}",
 *     tags={"open library"},
 *     summary="Preview a book from Open Library by ISBN",
 *     @OA\Parameter(name="isbn", in="path", required=true, description="ISBN-10 or ISBN-13 (hyphens allowed)", @OA\Schema(type="string"), example="9780451524935"),
 *     @OA\Response(response=200, description="Book data from Open Library", @OA\JsonContent(ref="#/components/schemas/OpenLibraryBook")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, description="No book with this ISBN on Open Library", @OA\JsonContent(ref="#/components/schemas/Error")),
 *     @OA\Response(response=503, description="Open Library is unavailable or too slow (timeout)", @OA\JsonContent(ref="#/components/schemas/Error",
 *         example={"message": "Open Library is not available right now. Please try again later or add the book manually."}))
 * )
 */
Flight::route('GET /openlibrary/isbn/@isbn', function ($isbn) {
    Flight::json(Flight::openLibraryService()->lookup($isbn));
});

/**
 * @OA\Post(
 *     path="/openlibrary/import",
 *     tags={"open library"},
 *     summary="Import a book from Open Library into the library",
 *     description="Looks up the ISBN, reuses or creates the author and adds the book.",
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"isbn"},
 *         @OA\Property(property="isbn", type="string", example="9780140449136"),
 *         @OA\Property(property="category_id", type="integer", nullable=true, example=5),
 *         @OA\Property(property="total_copies", type="integer", example=1)
 *     )),
 *     @OA\Response(response=201, description="The imported book", @OA\JsonContent(ref="#/components/schemas/BookDetails")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, description="No book with this ISBN on Open Library", @OA\JsonContent(ref="#/components/schemas/Error")),
 *     @OA\Response(response=503, description="Open Library is unavailable or too slow (timeout)", @OA\JsonContent(ref="#/components/schemas/Error"))
 * )
 */
Flight::route('POST /openlibrary/import', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::openLibraryService()->import($data), 201);
});
