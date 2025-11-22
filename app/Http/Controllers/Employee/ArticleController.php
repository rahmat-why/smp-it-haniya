<?php

namespace App\Http\Controllers\Employee;

use App\Models\MstArticle;
use App\Models\MstTagArticle;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    // Check authentication before accessing
    public function __construct()
    {
        if (session('user_type') !== 'Employee') {
            return redirect('/employee/login');
        }
    }

    // ============ ARTICLE CRUD ============

    /**
     * Display all articles with tag count
     */
    public function index()
    {
        // Avoid grouping/sorting on text/ntext columns (SQL Server restriction)
        // Aggregate the potentially large text column (content) with MAX after casting
        $articles = DB::select('
            SELECT a.article_id, a.title, a.slug,
                   MAX(CAST(a.content AS NVARCHAR(MAX))) AS content,
                   a.image, a.status, a.created_at, a.updated_at, a.created_by, a.updated_by,
                   COUNT(t.tag_id) as tag_count
            FROM mst_articles a
            LEFT JOIN mst_tag_articles t ON a.article_id = t.article_id
            GROUP BY a.article_id, a.title, a.slug, a.image, a.status, a.created_at, a.updated_at, a.created_by, a.updated_by
            ORDER BY a.article_id DESC
        ');

        // Fetch all tags for the returned articles in one query and attach them to each article
        $articleIds = array_map(function ($a) { return $a->article_id; }, $articles);
        $tagsByArticle = [];
        if (!empty($articleIds)) {
            $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
            $tagRows = DB::select(
                "SELECT t.article_id, t.tag_code, d.item_name FROM mst_tag_articles t LEFT JOIN mst_detail_settings d ON t.tag_code = d.item_code AND d.header_id = 'TAG_ARTICLE' WHERE t.article_id IN ($placeholders) ORDER BY t.tag_id DESC",
                $articleIds
            );

            foreach ($tagRows as $r) {
                $label = $r->item_name ?? $r->tag_code;
                $tagsByArticle[$r->article_id][] = $label;
            }
        }

        // Attach tags array to each article (empty array if none)
        foreach ($articles as $a) {
            $a->tags = $tagsByArticle[$a->article_id] ?? [];
        }

        return view('employee.articles.index', compact('articles'));
    }

    /**
     * Show form for creating new article
     */
    public function create()
    {
        // Load tag options from settings for the create form
        $availableTags = DB::select(
            "SELECT item_code AS tag_code, item_name FROM mst_detail_settings WHERE header_id = ? AND status = ? ORDER BY item_name",
            ['TAG_ARTICLE', 'Active']
        );

        return view('employee.articles.create', compact('availableTags'));
    }

    /**
     * Store new article in database
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'article_id' => 'required|string|max:50|unique:mst_articles',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:mst_articles',
            'content' => 'required|string',
            'image' => 'nullable|string|max:500',
            'status' => 'required|in:Draft,Published,Archived',
            'tag_codes' => 'nullable|array',
            'tag_codes.*' => 'required_with:tag_codes|string|max:50',
        ]);

        // If tag_codes provided, ensure they exist in mst_detail_settings for TAG_ARTICLE
        if (!empty($validated['tag_codes'])) {
            $rows = DB::select(
                "SELECT item_code FROM mst_detail_settings WHERE header_id = ? AND status = ? AND item_code IN (" .
                implode(',', array_fill(0, count($validated['tag_codes']), '?')) . ")",
                array_merge(['TAG_ARTICLE', 'Active'], $validated['tag_codes'])
            );
            $found = array_map(function ($r) { return $r->item_code; }, $rows);
            $diff = array_diff($validated['tag_codes'], $found);
            if (!empty($diff)) {
                return back()->withInput()->withErrors(['tag_codes' => 'One or more selected tags are invalid.']);
            }
        }

        $validated['created_by'] = session('employee_id');
        $validated['updated_by'] = session('employee_id');
        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        // Use transaction: create article and insert tags if provided
        DB::beginTransaction();
        try {
            MstArticle::create($validated);

            if (!empty($validated['tag_codes'])) {
                foreach ($validated['tag_codes'] as $tagCode) {
                    $tagId = $validated['article_id'] . '_' . $tagCode;

                    // Avoid duplicate
                    $exists = DB::select('SELECT TOP (1) 1 AS found FROM mst_tag_articles WHERE tag_id = ?', [$tagId]);
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

            return redirect()->route('employee.articles.index')
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

        return view('employee.articles.edit', [
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
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:mst_articles,slug,' . $id . ',article_id',
            'content' => 'required|string',
            'image' => 'nullable|string|max:500',
            'status' => 'required|in:Draft,Published,Archived',
            'tag_codes' => 'nullable|array',
            'tag_codes.*' => 'required_with:tag_codes|string|max:50',
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
            return redirect()->route('employee.articles.index')
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

        return view('employee.articles.index-tag', [
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

        return view('employee.articles.create-tag', [
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
