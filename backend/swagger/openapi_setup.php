<?php
/**
 * General OpenAPI information and the data schemas shared by the endpoints.
 * This file contains only documentation; it is read by swagger.php, never executed by the API.
 *
 * @OA\Info(
 *     title="BookNest API",
 *     version="1.0.0",
 *     description="REST API of the BookNest library application (IT 2001 - Web Programming, IBU). All responses are JSON. Errors have the form {""message"": ""..."", ""errors"": {""field"": ""...""}}. Any endpoint can return 500 with a general message if an unexpected server error happens. Authentication with JWT is added in Milestone 4."
 * )
 *
 * @OA\Tag(name="books", description="Books in the catalog")
 * @OA\Tag(name="authors", description="Book authors")
 * @OA\Tag(name="categories", description="Book categories")
 * @OA\Tag(name="loans", description="Borrowing and returning books")
 * @OA\Tag(name="reviews", description="Ratings and comments")
 * @OA\Tag(name="users", description="Members and administrators")
 * @OA\Tag(name="open library", description="External API: import books by ISBN")
 *
 * ---------------------------------------------------------------- Reusable responses
 *
 * @OA\Response(
 *     response="NotFound",
 *     description="The record does not exist",
 *     @OA\JsonContent(ref="#/components/schemas/Error", example={"message": "Book not found."})
 * )
 * @OA\Response(
 *     response="BadRequest",
 *     description="Validation failed or a business rule was broken",
 *     @OA\JsonContent(ref="#/components/schemas/Error", example={"message": "Validation failed.", "errors": {"title": "title is required."}})
 * )
 * @OA\Response(
 *     response="Deleted",
 *     description="The record was deleted",
 *     @OA\JsonContent(@OA\Property(property="message", type="string", example="Deleted successfully."))
 * )
 *
 * ---------------------------------------------------------------- Schemas
 *
 * @OA\Schema(
 *     schema="Error",
 *     @OA\Property(property="message", type="string", example="Validation failed."),
 *     @OA\Property(property="errors", type="object", description="Field name -> error message (only for validation errors)", example={"email": "This email is already registered."})
 * )
 *
 * @OA\Schema(
 *     schema="AuthorInput",
 *     required={"first_name", "last_name"},
 *     @OA\Property(property="first_name", type="string", maxLength=50, example="Meša"),
 *     @OA\Property(property="last_name", type="string", maxLength=50, example="Selimović"),
 *     @OA\Property(property="nationality", type="string", maxLength=50, nullable=true, example="Bosnian"),
 *     @OA\Property(property="birth_year", type="integer", nullable=true, example=1910),
 *     @OA\Property(property="biography", type="string", nullable=true, example="Writer from Tuzla.")
 * )
 * @OA\Schema(
 *     schema="Author",
 *     allOf={
 *         @OA\Schema(@OA\Property(property="id", type="integer", example=5)),
 *         @OA\Schema(ref="#/components/schemas/AuthorInput"),
 *         @OA\Schema(@OA\Property(property="book_count", type="integer", example=1, description="Only in the list of all authors"))
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="CategoryInput",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", maxLength=50, example="Poetry"),
 *     @OA\Property(property="description", type="string", maxLength=255, nullable=true, example="Poems and verse")
 * )
 * @OA\Schema(
 *     schema="Category",
 *     allOf={
 *         @OA\Schema(@OA\Property(property="id", type="integer", example=1)),
 *         @OA\Schema(ref="#/components/schemas/CategoryInput"),
 *         @OA\Schema(@OA\Property(property="book_count", type="integer", example=2, description="Only in the list of all categories"))
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="BookInput",
 *     required={"title", "author_id"},
 *     @OA\Property(property="title", type="string", maxLength=150, example="The Fortress"),
 *     @OA\Property(property="isbn", type="string", nullable=true, description="10 or 13 digits; hyphens are removed", example="9780810127166"),
 *     @OA\Property(property="publication_year", type="integer", nullable=true, example=1970),
 *     @OA\Property(property="description", type="string", nullable=true, example="A soldier returns to Sarajevo after the war."),
 *     @OA\Property(property="cover_url", type="string", format="uri", nullable=true, example="https://covers.openlibrary.org/b/id/123-M.jpg"),
 *     @OA\Property(property="total_copies", type="integer", minimum=1, example=2, description="Defaults to 1. available_copies is calculated by the server."),
 *     @OA\Property(property="author_id", type="integer", example=5),
 *     @OA\Property(property="category_id", type="integer", nullable=true, example=5)
 * )
 * @OA\Schema(
 *     schema="Book",
 *     allOf={
 *         @OA\Schema(@OA\Property(property="id", type="integer", example=1)),
 *         @OA\Schema(ref="#/components/schemas/BookInput"),
 *         @OA\Schema(
 *             @OA\Property(property="available_copies", type="integer", example=1),
 *             @OA\Property(property="author_name", type="string", example="Meša Selimović"),
 *             @OA\Property(property="category_name", type="string", nullable=true, example="Classics")
 *         )
 *     }
 * )
 * @OA\Schema(
 *     schema="BookDetails",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Book"),
 *         @OA\Schema(@OA\Property(
 *             property="rating",
 *             @OA\Property(property="average", type="number", nullable=true, example=4.5),
 *             @OA\Property(property="count", type="integer", example=2)
 *         ))
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="LoanInput",
 *     required={"user_id", "book_id"},
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(property="book_id", type="integer", example=4),
 *     @OA\Property(property="loan_date", type="string", format="date", description="Defaults to today", example="2026-11-22"),
 *     @OA\Property(property="due_date", type="string", format="date", description="Defaults to loan_date + 14 days", example="2026-12-06")
 * )
 * @OA\Schema(
 *     schema="Loan",
 *     @OA\Property(property="id", type="integer", example=7),
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(property="book_id", type="integer", example=4),
 *     @OA\Property(property="loan_date", type="string", format="date", example="2026-11-22"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2026-12-06"),
 *     @OA\Property(property="return_date", type="string", format="date", nullable=true, example=null),
 *     @OA\Property(property="status", type="string", enum={"borrowed", "returned", "overdue"}, example="borrowed"),
 *     @OA\Property(property="book_title", type="string", example="The Fellowship of the Ring", description="Only in lists"),
 *     @OA\Property(property="user_name", type="string", example="Emir Kovač", description="Only in lists")
 * )
 *
 * @OA\Schema(
 *     schema="ReviewInput",
 *     required={"user_id", "book_id", "rating"},
 *     @OA\Property(property="user_id", type="integer", example=4),
 *     @OA\Property(property="book_id", type="integer", example=9),
 *     @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
 *     @OA\Property(property="comment", type="string", maxLength=1000, nullable=true, example="Beautifully written.")
 * )
 * @OA\Schema(
 *     schema="ReviewUpdate",
 *     @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
 *     @OA\Property(property="comment", type="string", maxLength=1000, nullable=true, example="Even better the second time.")
 * )
 * @OA\Schema(
 *     schema="Review",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=3),
 *     @OA\Property(property="book_id", type="integer", example=1),
 *     @OA\Property(property="rating", type="integer", example=5),
 *     @OA\Property(property="comment", type="string", nullable=true, example="A must-read."),
 *     @OA\Property(property="created_at", type="string", example="2026-10-12 18:30:00"),
 *     @OA\Property(property="book_title", type="string", example="1984", description="Only in lists"),
 *     @OA\Property(property="user_name", type="string", example="Lejla Begić", description="Only in lists")
 * )
 *
 * @OA\Schema(
 *     schema="UserInput",
 *     required={"first_name", "last_name", "email", "password"},
 *     @OA\Property(property="first_name", type="string", maxLength=50, example="Amina"),
 *     @OA\Property(property="last_name", type="string", maxLength=50, example="Hodžić"),
 *     @OA\Property(property="email", type="string", format="email", example="amina@example.com"),
 *     @OA\Property(property="password", type="string", format="password", minLength=6, example="secret123", description="Stored only as a bcrypt hash"),
 *     @OA\Property(property="role", type="string", enum={"admin", "member"}, example="member", description="Defaults to member")
 * )
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="integer", example=2),
 *     @OA\Property(property="first_name", type="string", example="Emir"),
 *     @OA\Property(property="last_name", type="string", example="Kovač"),
 *     @OA\Property(property="email", type="string", example="emir@example.com"),
 *     @OA\Property(property="role", type="string", enum={"admin", "member"}, example="member"),
 *     @OA\Property(property="created_at", type="string", example="2026-09-12 14:20:00")
 * )
 *
 * @OA\Schema(
 *     schema="OpenLibraryBook",
 *     @OA\Property(property="isbn", type="string", example="9780451524935"),
 *     @OA\Property(property="title", type="string", example="1984"),
 *     @OA\Property(property="author_name", type="string", example="George Orwell"),
 *     @OA\Property(property="publication_year", type="integer", nullable=true, example=1950),
 *     @OA\Property(property="publishers", type="array", @OA\Items(type="string"), example={"Signet Classic"}),
 *     @OA\Property(property="number_of_pages", type="integer", nullable=true, example=328),
 *     @OA\Property(property="cover_url", type="string", nullable=true, example="https://covers.openlibrary.org/b/id/9267242-M.jpg"),
 *     @OA\Property(property="subjects", type="array", @OA\Items(type="string"), example={"Dystopias", "Totalitarianism"}),
 *     @OA\Property(property="already_in_library", type="boolean", example=true),
 *     @OA\Property(property="book_id", type="integer", nullable=true, example=1, description="Our book id if it is already in the library")
 * )
 */
