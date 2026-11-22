<?php

/**
 * @OA\Get(
 *     path="/loans",
 *     tags={"loans"},
 *     summary="List loans with book title and member name (overdue loans are marked first)",
 *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"borrowed", "returned", "overdue"})),
 *     @OA\Response(response=200, description="List of loans", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Loan"))),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
 * )
 */
Flight::route('GET /loans', function () {
    Flight::json(Flight::loanService()->getAll(Flight::request()->query['status']));
});

/**
 * @OA\Get(
 *     path="/loans/{id}",
 *     tags={"loans"},
 *     summary="Get one loan",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, description="The loan", @OA\JsonContent(ref="#/components/schemas/Loan")),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('GET /loans/@id:[0-9]+', function ($id) {
    Flight::json(Flight::loanService()->getById((int) $id));
});

/**
 * @OA\Post(
 *     path="/loans",
 *     tags={"loans"},
 *     summary="Borrow a book (takes one available copy)",
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/LoanInput")),
 *     @OA\Response(response=201, description="The created loan", @OA\JsonContent(ref="#/components/schemas/Loan")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
 * )
 */
Flight::route('POST /loans', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::loanService()->create($data), 201);
});

/**
 * @OA\Put(
 *     path="/loans/{id}",
 *     tags={"loans"},
 *     summary="Change the due date of a loan (e.g. extend it)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"due_date"},
 *         @OA\Property(property="due_date", type="string", format="date", example="2026-12-20")
 *     )),
 *     @OA\Response(response=200, description="The updated loan", @OA\JsonContent(ref="#/components/schemas/Loan")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('PUT /loans/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::loanService()->update((int) $id, $data));
});

/**
 * @OA\Put(
 *     path="/loans/{id}/return",
 *     tags={"loans"},
 *     summary="Return a borrowed book (the copy becomes available again)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, description="The returned loan", @OA\JsonContent(ref="#/components/schemas/Loan")),
 *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('PUT /loans/@id:[0-9]+/return', function ($id) {
    Flight::json(Flight::loanService()->returnLoan((int) $id));
});

/**
 * @OA\Delete(
 *     path="/loans/{id}",
 *     tags={"loans"},
 *     summary="Delete a loan (if it was not returned, the copy becomes available again)",
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), example=1),
 *     @OA\Response(response=200, ref="#/components/responses/Deleted"),
 *     @OA\Response(response=404, ref="#/components/responses/NotFound")
 * )
 */
Flight::route('DELETE /loans/@id:[0-9]+', function ($id) {
    Flight::loanService()->delete((int) $id);
    Flight::json(['message' => 'Loan deleted successfully.']);
});
