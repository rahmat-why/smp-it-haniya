<?php

namespace App\Http\Controllers\Employee;

use App\Models\MstArticle;
use App\Models\MstTagArticle;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ArticleController extends Controller
{
    public function index()
{
    // Halaman akan memuat DataTables AJAX
    return view('articles.index');
}

/**
 * Return JSON untuk DataTables / AJAX
 */
public function getData()
{
    // Ambil data dasar terlebih dahulu
    $articles = DB::table('mst_articles as a')
        ->select(
            'a.article_id',
            'a.title',
            'a.slug',
            'a.content',
            'a.image',
            'a.status',
            'a.created_at',
            'a.updated_at',
            'a.created_by',
            'a.updated_by',
            DB::raw('(SELECT COUNT(*) FROM mst_tag_articles WHERE article_id = a.article_id) AS tag_count')
        );

    return DataTables::of($articles)
        ->addColumn('tags', function ($row) {
            // Ambil tag untuk setiap artikel
            $tags = DB::table('mst_tag_articles as t')
                ->leftJoin('mst_detail_settings as d', function ($join) {
                    $join->on('t.tag_code', '=', 'd.item_code')
                        ->where('d.header_id', '=', 'TAG_ARTICLE');
                })
                ->where('t.article_id', $row->article_id)
                ->orderBy('t.tag_id', 'DESC')
                ->get();

            // Ambil label tag
            $list = [];
            foreach ($tags as $t) {
                $list[] = $t->item_name ?? $t->tag_code;
            }

            return implode(', ', $list);
        })
        ->addColumn('action', function ($row) {

            $editUrl = url('articles/edit/' . $row->article_id);
            $deleteUrl = url('articles/' . $row->article_id);
            $csrf = csrf_token();

            $form  = "<form action='{$deleteUrl}' method='POST' style='display:inline;' onsubmit=\"return confirm('Yakin hapus data ini?');\">";
            $form .= "<input type='hidden' name='_token' value='{$csrf}'>";
            $form .= "<input type='hidden' name='_method' value='DELETE'>";
            $form .= "<button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i> Delete</button>";
            $form .= "</form>";

            return "<a href='{$editUrl}' class='btn btn-sm btn-warning me-1'><i class='fas fa-edit'></i> Edit</a>" . $form;
        })
        ->rawColumns(['action'])
        ->make(true);
}


    /**
     * Show form for creating new article
     */
   public function create()
{
    // Load tag options from settings for the create form
    $availableTags = DB::select(
        "SELECT item_code AS tag_code, item_name 
         FROM mst_detail_settings 
         WHERE header_id = ? AND status = ? 
         ORDER BY item_name",
        ['TAG_ARTICLE', 'Active']
    );

    // Generate New Article ID
    $last = DB::table('mst_articles')->orderBy('article_id', 'desc')->first();

    if ($last && preg_match('/(\d+)/', $last->article_id, $m)) {
        $num = intval($m[1]) + 1;
        $newArticleId = 'ART' . str_pad($num, 5, '0', STR_PAD_LEFT);
    } else {
        $newArticleId = 'ART00001';
    }

    return view('articles.create', compact('availableTags', 'newArticleId'));
}


    /**
     * Store new article in database
     */
   public function store(Request $request)
{
   $validated = $request->validate([
    // ARTICLE ID – wajib string dan unik
    'article_id' => [
        'required',
        'string',
        'unique:mst_articles,article_id',
    ],

    // TITLE – hanya huruf, angka, dan spasi
    'title' => [
        'required',
        'regex:/^[A-Za-z0-9 ]+$/',
        'max:255',
    ],

    // SLUG – huruf kecil, angka, dan tanda minus
  'slug' => ['nullable', 'string', 'max:255'],


    // CONTENT
    'content' => ['required', 'string'],

    // IMAGE opsional
    'image' => ['nullable', 'string', 'max:500'],

    // STATUS
    'status' => ['required', 'in:Draft,Published,Archived'],

    // TAGS
    'tag_codes' => ['nullable', 'array'],
    'tag_codes.*' => ['required_with:tag_codes', 'string', 'max:50'],
]);

// Pastikan ID tetap dari input readonly
$validated['article_id'] = $request->input('article_id');



    // VALIDASI: Pastikan tag benar-benar ada di mst_detail_settings
    if (!empty($validated['tag_codes'])) {
        $rows = DB::select(
            "SELECT item_code FROM mst_detail_settings WHERE header_id = ? AND status = ? AND item_code IN (" .
            implode(',', array_fill(0, count($validated['tag_codes']), '?')) . ")",
            array_merge(['TAG_ARTICLE', 'Active'], $validated['tag_codes'])
        );

        $found = array_map(fn($r) => $r->item_code, $rows);
        $diff  = array_diff($validated['tag_codes'], $found);

        if (!empty($diff)) {
            return back()->withInput()->withErrors(['tag_codes' => 'One or more selected tags are invalid.']);
        }
    }

    $validated['created_by'] = session('employee_id');
    $validated['updated_by'] = session('employee_id');
    $validated['created_at'] = now();
    $validated['updated_at'] = now();

    DB::beginTransaction();
    try {
        MstArticle::create($validated);

        if (!empty($validated['tag_codes'])) {
            foreach ($validated['tag_codes'] as $tagCode) {
                $tagId = $validated['article_id'] . '_' . $tagCode;

                $exists = DB::select(
                    'SELECT TOP (1) 1 AS found FROM mst_tag_articles WHERE tag_id = ?',
                    [$tagId]
                );

                if (empty($exists)) {
                    MstTagArticle::create([
                        'tag_id' => $tagId,
                        'article_id' => $validated['article_id'],
                        'tag_code' => $tagCode,
                        'created_by' => session('employee_id'),
                        'updated_by' => session('employee_id'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        DB::commit();

        return redirect()
            ->route('employee.articles.index')
            ->with('success', 'Article created successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}


    /**
     * Show form for editing article
     */
    public function edit($id)
    {
        $article = DB::select(
            'SELECT * FROM mst_articles WHERE article_id = ?',
            [$id]
        );

        if (empty($article)) {
            return redirect()->route('employee.articles.index')
                           ->with('error', 'Article not found!');
        }

        // Load available tags and assigned tags for edit form
        $availableTags = DB::select(
            "SELECT item_code AS tag_code, item_name FROM mst_detail_settings WHERE header_id = ? AND status = ? ORDER BY item_name",
            ['TAG_ARTICLE', 'Active']
        );

        $assignedTags = DB::select('SELECT tag_code FROM mst_tag_articles WHERE article_id = ?', [$id]);
        $assignedTagCodes = array_map(function($t){ return $t->tag_code; }, $assignedTags);

        return view('articles.edit', [
            'article' => $article[0],
            'availableTags' => $availableTags,
            'assignedTags' => $assignedTagCodes,
        ]);
    }

    /**
     * Update article in database
     */
    public function update(Request $request, $id)
    {
        $article = MstArticle::findOrFail($id);
        $validated = $request->validate([
    'title' => ['required', 'regex:/^[A-Za-z0-9 ]+$/', 'max:255'],
    'slug'  => [
        'required', 
        'regex:/^[a-z0-9-]+$/',
        'max:255',
        'unique:mst_articles,slug,' . $id . ',article_id'
    ],
    'content' => ['required'],
    'image' => ['nullable', 'regex:/^[A-Za-z0-9._\/\-]+$/', 'max:500'],
    'status' => ['required', 'in:Draft,Published,Archived'],
    'tag_codes' => ['nullable', 'array'],
    'tag_codes.*' => ['required_with:tag_codes', 'regex:/^[A-Za-z0-9]+$/', 'max:50'],
]);


        $validated['updated_by'] = session('employee_id');
        $validated['updated_at'] = now();

        DB::beginTransaction();
        try {
            // Update article fields
            $article->update([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'content' => $validated['content'],
                'image' => $validated['image'] ?? null,
                'status' => $validated['status'],
                'updated_by' => $validated['updated_by'],
                'updated_at' => $validated['updated_at'],
            ]);

            // Validate provided tag_codes exist in settings
            $submittedTags = $validated['tag_codes'] ?? [];
            if (!empty($submittedTags)) {
                $rows = DB::select(
                    "SELECT item_code FROM mst_detail_settings WHERE header_id = ? AND status = ? AND item_code IN (" .
                    implode(',', array_fill(0, count($submittedTags), '?')) . ")",
                    array_merge(['TAG_ARTICLE', 'Active'], $submittedTags)
                );
                $found = array_map(function ($r) { return $r->item_code; }, $rows);
                $diff = array_diff($submittedTags, $found);
                if (!empty($diff)) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(['tag_codes' => 'One or more selected tags are invalid.']);
                }
            }

            // Sync tags
            $existing = DB::select('SELECT tag_code FROM mst_tag_articles WHERE article_id = ?', [$id]);
            $existingCodes = array_map(function($r){ return $r->tag_code; }, $existing);

            $toAdd = array_diff($submittedTags, $existingCodes);
            $toRemove = array_diff($existingCodes, $submittedTags);

            if (!empty($toRemove)) {
                DB::delete(
                    'DELETE FROM mst_tag_articles WHERE article_id = ? AND tag_code IN (' . implode(',', array_fill(0, count($toRemove), '?')) . ')',
                    array_merge([$id], $toRemove)
                );
            }

            foreach ($toAdd as $tagCode) {
                $tagId = $id . '_' . $tagCode;
                MstTagArticle::create([
                    'tag_id' => $tagId,
                    'article_id' => $id,
                    'tag_code' => $tagCode,
                    'created_by' => session('employee_id'),
                    'updated_by' => session('employee_id'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('employee.articles.index')
                           ->with('success', 'Article updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating article: ' . $e->getMessage());
        }
    }

    /**
     * Delete article and all its tags
     */
    public function destroy($id)
    {
        $article = MstArticle::findOrFail($id);

        // Delete all tags first
        MstTagArticle::where('article_id', $id)->delete();

        // Delete article
        $article->delete();

        return redirect()->route('employee.articles.index')
                       ->with('success', 'Article and all tags deleted successfully!');
    }

    // ============ TAG ARTICLE CRUD ============

    /**
     * Display all tags for an article
     */
    public function indexTag($articleId)
    {
        // Check if article exists
        $article = DB::select(
            'SELECT * FROM mst_articles WHERE article_id = ?',
            [$articleId]
        );

        if (empty($article)) {
            return redirect()->route('articles.index')
                           ->with('error', 'Article not found!');
        }

        // Fetch tags and include human-friendly name from mst_detail_settings if available
        $tags = DB::select(
            "SELECT t.*, d.item_name FROM mst_tag_articles t\n             LEFT JOIN mst_detail_settings d ON t.tag_code = d.item_code AND d.header_id = 'TAG_ARTICLE'\n             WHERE t.article_id = ? ORDER BY t.tag_id DESC",
            [$articleId]
        );

        // Get tag options from mst_detail_settings where header_id = 'TAG_ARTICLE'
        $availableTags = DB::select(
            "SELECT item_code AS tag_code, item_name FROM mst_detail_settings WHERE header_id = ? AND status = ? ORDER BY item_name",
            ['TAG_ARTICLE', 'Active']
        );

        return view('articles.index-tag', [
            'article' => $article[0],
            'tags' => $tags,
            'availableTags' => $availableTags
        ]);
    }

    /**
     * Show form for creating multiple tags at once (Select2 multiple)
     */
    public function createTag($articleId)
    {
        // Check if article exists
        $article = DB::select(
            'SELECT * FROM mst_articles WHERE article_id = ?',
            [$articleId]
        );

        if (empty($article)) {
            return redirect()->route('employee.articles.index')
                           ->with('error', 'Article not found!');
        }

        // Get tag options from mst_detail_settings where header_id = 'TAG_ARTICLE'
        $availableTags = DB::select(
            "SELECT item_code AS tag_code, item_name FROM mst_detail_settings WHERE header_id = ? AND status = ? ORDER BY item_name",
            ['TAG_ARTICLE', 'Active']
        );

        // Get already assigned tags
        $assignedTags = DB::select(
            'SELECT tag_code FROM mst_tag_articles WHERE article_id = ?',
            [$articleId]
        );

        $assignedTagCodes = array_map(function($tag) {
            return $tag->tag_code;
        }, $assignedTags);

        return view('articles.create-tag', [
            'article' => $article[0],
            'availableTags' => $availableTags,
            'assignedTags' => $assignedTagCodes
        ]);
    }

    /**
     * Store multiple tags for article (handles Select2 multiple selection)
     */
    public function storeTag(Request $request, $articleId)
    {
        $validated = $request->validate([
            'tag_codes' => 'required|array|min:1',
            'tag_codes.*' => 'required|string|max:50',
        ]);

        // Get existing tags for this article
        $existingTags = DB::select(
            'SELECT tag_code FROM mst_tag_articles WHERE article_id = ?',
            [$articleId]
        );

        $existingTagCodes = array_map(function($tag) {
            return $tag->tag_code;
        }, $existingTags);

        // Determine which tags to add and which to remove
        $tagsToAdd = array_diff($validated['tag_codes'], $existingTagCodes);
        $tagsToRemove = array_diff($existingTagCodes, $validated['tag_codes']);

        // Remove old tags
        if (!empty($tagsToRemove)) {
            DB::delete(
                'DELETE FROM mst_tag_articles WHERE article_id = ? AND tag_code IN (' . 
                implode(',', array_fill(0, count($tagsToRemove), '?')) . ')',
                array_merge([$articleId], $tagsToRemove)
            );
        }

        // Add new tags
        foreach ($tagsToAdd as $tagCode) {
            $tagId = $articleId . '_' . $tagCode;

            MstTagArticle::create([
                'tag_id' => $tagId,
                'article_id' => $articleId,
                'tag_code' => $tagCode,
                'created_by' => session('employee_id'),
                'updated_by' => session('employee_id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('employee.articles.tag', $articleId)
                       ->with('success', count($tagsToAdd) . ' tag(s) added successfully!');
    }

    /**
     * Delete a specific tag from article
     */
    public function destroyTag($articleId, $tagId)
    {
        $tag = MstTagArticle::findOrFail($tagId);
        $tag->delete();

        return redirect()->route('employee.articles.tag', $articleId)
                       ->with('success', 'Tag deleted successfully!');
    }
}
