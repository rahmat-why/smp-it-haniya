using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.IO;
using System.Linq;
using System.Text.RegularExpressions;
using Haniya.Models;

namespace Haniya.Controllers.PortalAdmin
{
    public class ArticleController : Controller
    {
        private readonly IConfiguration _config;

        public ArticleController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        // ================== SLUG HELPERS ==================

        private string GenerateSlug(string title)
        {
            if (string.IsNullOrWhiteSpace(title))
                return "article";

            var slug = title.Trim().ToLowerInvariant();

            // replace non alphanumeric with '-'
            slug = Regex.Replace(slug, @"[^a-z0-9]+", "-");
            // trim '-'
            slug = slug.Trim('-');

            if (string.IsNullOrWhiteSpace(slug))
                slug = "article";

            return slug;
        }

        private string GenerateUniqueSlug(SqlConnection conn, string title)
        {
            var baseSlug = GenerateSlug(title);
            var slug = baseSlug;
            var suffix = 2;

            while (true)
            {
                var checkCmd = new SqlCommand(
                    "SELECT COUNT(1) FROM mst_articles WHERE slug = @slug",
                    conn
                );
                checkCmd.Parameters.AddWithValue("@slug", slug);
                var count = Convert.ToInt32(checkCmd.ExecuteScalar() ?? 0);

                if (count == 0)
                    break;

                slug = $"{baseSlug}-{suffix}";
                suffix++;
            }

            return slug;
        }

        private int ExtractTrailingNumber(string value)
        {
            if (string.IsNullOrWhiteSpace(value))
                return 0;

            var match = Regex.Match(value, @"(\d+)$");
            if (!match.Success)
                return 0;

            return int.TryParse(match.Groups[1].Value, out var n) ? n : 0;
        }

        // ================== PAGE ==================

        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/Article/Index.cshtml");
        }

        public IActionResult Create()
        {
            return View("~/Views/PortalAdmin/Article/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.articleId = id;
            return View("~/Views/PortalAdmin/Article/Edit.cshtml");
        }

        /* ===================== API ===================== */
        public class ListSort
        {
            public string field { get; set; } = "createdAt";
            public string order { get; set; } = "desc";
        }

        public class ListRequest
        {
            public int page { get; set; } = 1;
            public int limit { get; set; } = 10;
            public Dictionary<string, string>? filters { get; set; }
            public ListSort? sort { get; set; }
        }

        [HttpPost]
        public IActionResult GetAll([FromBody] ListRequest? req)
        {
            try
            {
                req ??= new ListRequest();
                var page = req.page <= 0 ? 1 : req.page;
                var limit = req.limit <= 0 ? 10 : Math.Min(req.limit, 50);

                var filters = req.filters ?? new Dictionary<string, string>();
                filters.TryGetValue("search", out var searchValue);
                filters.TryGetValue("status", out var statusFilter);

                var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
                {
                    ["title"] = "a.title",
                    ["slug"] = "a.slug",
                    ["status"] = "a.status",
                    ["content"] = "a.content",
                    ["tags"] = "STRING_AGG(t.tag_code, ', ')",
                    ["createdAt"] = "a.created_at"
                };
                var sort = req.sort ?? new ListSort();
                var orderColumn = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "a.created_at";
                var orderDir = string.Equals(sort.order, "asc", StringComparison.OrdinalIgnoreCase) ? "ASC" : "DESC";

                using var conn = GetConn();
                conn.Open();

                // Total records
                var totalCmd = new SqlCommand("SELECT COUNT(*) FROM mst_articles", conn);
                var recordsTotal = (int)totalCmd.ExecuteScalar();

                // Filtered count
                string whereSearch = " WHERE 1=1 ";
                if (!string.IsNullOrWhiteSpace(searchValue))
                {
                    whereSearch += @" AND (
                title LIKE @search OR
                slug LIKE @search OR
                status LIKE @search
            )";
                }
                if (!string.IsNullOrWhiteSpace(statusFilter))
                    whereSearch += " AND status = @statusFilter ";

                var filteredCmd = new SqlCommand("SELECT COUNT(*) FROM mst_articles" + whereSearch, conn);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    filteredCmd.Parameters.AddWithValue("@search", $"%{searchValue}%");
                if (!string.IsNullOrWhiteSpace(statusFilter))
                    filteredCmd.Parameters.AddWithValue("@statusFilter", statusFilter);
                var recordsFiltered = (int)filteredCmd.ExecuteScalar();

                var totalPages = recordsFiltered == 0 ? 1 : (int)Math.Ceiling(recordsFiltered / (double)limit);
                page = Math.Min(page, totalPages);
                var offset = (page - 1) * limit;

                // Main data query - now includes tags and content
                var sql = $@"
            SELECT 
                a.article_id,
                a.title,
                a.slug,
                a.status,
                a.image,
                a.content,
                a.created_at,
                STRING_AGG(t.tag_code, ', ') AS tags
            FROM mst_articles a
            LEFT JOIN mst_tag_articles t ON t.article_id = a.article_id
            {whereSearch}
            GROUP BY 
                a.article_id,
                a.title,
                a.slug,
                a.status,
                a.image,
                a.content,
                a.created_at
            ORDER BY {orderColumn} {orderDir}
            OFFSET @start ROWS FETCH NEXT @length ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@start", offset);
                cmd.Parameters.AddWithValue("@length", limit);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    cmd.Parameters.AddWithValue("@search", $"%{searchValue}%");
                if (!string.IsNullOrWhiteSpace(statusFilter))
                    cmd.Parameters.AddWithValue("@statusFilter", statusFilter);

                var list = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    list.Add(new
                    {
                        article_id = rd["article_id"],
                        title = rd["title"],
                        slug = rd["slug"],
                        status = rd["status"],
                        image = rd["image"],
                        content = rd["content"]?.ToString() ?? "",
                        created_at = rd["created_at"],
                        tags = rd["tags"]?.ToString() ?? ""
                    });
                }

                var hasNextPage = (offset + list.Count) < recordsFiltered;
                return Json(DTOResponse.ok(new
                {
                    data = list,
                    hasNextPage,
                    totalRows = recordsFiltered,
                    totalAll = recordsTotal,
                    currentPage = page,
                    limit
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult GetById(string id)
        {
            try
            {
                using var conn = GetConn();
                conn.Open();

                var sql = "SELECT * FROM mst_articles WHERE article_id = @id";
                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);

                using var rd = cmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("data not found", 404));

                var art = new
                {
                    article_id = rd["article_id"]?.ToString(),
                    title = rd["title"]?.ToString(),
                    slug = rd["slug"]?.ToString(),
                    content = rd["content"]?.ToString(),
                    image = rd["image"]?.ToString(),
                    status = rd["status"]?.ToString()
                };

                rd.Close();

                // Tags
                var tags = new List<string>();
                var tagSql = @"
                    SELECT tag_code
                    FROM mst_tag_articles
                    WHERE article_id = @id
                    ORDER BY created_at";

                using var tagCmd = new SqlCommand(tagSql, conn);
                tagCmd.Parameters.AddWithValue("@id", id);

                using var tagRd = tagCmd.ExecuteReader();
                while (tagRd.Read())
                {
                    var code = tagRd["tag_code"]?.ToString();
                    if (!string.IsNullOrWhiteSpace(code))
                        tags.Add(code);
                }

                return Json(DTOResponse.ok(new
                {
                    art.article_id,
                    art.title,
                    art.slug,
                    art.content,
                    art.image,
                    art.status,
                    tags = tags,
                    tags_text = string.Join(", ", tags)
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        [RequestSizeLimit(50 * 1024 * 1024)] // 50MB
        public IActionResult Create(DTORequest req)
        {
            try
            {
                var f = Request.Form;
                var file = Request.Form.Files["image"];

                var title = f["title"].ToString();
                var content = f["content"].ToString();
                var rawTags = string.Join(",", f["tags"].ToArray());

                if (string.IsNullOrWhiteSpace(title))
                    return Json(DTOResponse.fail("title is required", 400));

                if (string.IsNullOrWhiteSpace(content))
                    return Json(DTOResponse.fail("content is required", 400));

                var tagCodes = rawTags
                    .Split(new[] { ',' }, StringSplitOptions.RemoveEmptyEntries)
                    .Select(t => t.Trim())
                    .Where(t => !string.IsNullOrWhiteSpace(t))
                    .Distinct(StringComparer.OrdinalIgnoreCase)
                    .ToList();

                if (tagCodes.Count == 0)
                    return Json(DTOResponse.fail("tags are required", 400));

                using var conn = GetConn();
                conn.Open();

                // generate article_id
                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(article_id),'ART0000') FROM mst_articles",
                    conn
                );
                var lastId = lastCmd.ExecuteScalar()?.ToString() ?? "ART0000";
                var next = ExtractTrailingNumber(lastId) + 1;
                var articleId = "ART" + next.ToString("D4");

                // generate unique slug
                var slug = GenerateUniqueSlug(conn, title);

                // upload image
                string imagePath = null;
                if (file != null && file.Length > 0)
                {
                    var folder = Path.Combine(
                        Directory.GetCurrentDirectory(),
                        "wwwroot/image/article"
                    );

                    if (!Directory.Exists(folder))
                        Directory.CreateDirectory(folder);

                    var fileName = articleId + Path.GetExtension(file.FileName);
                    var fullPath = Path.Combine(folder, fileName);

                    using var stream = new FileStream(fullPath, FileMode.Create);
                    file.CopyTo(stream);

                    imagePath = "/image/article/" + fileName;
                }

                var sql = @"
                    INSERT INTO mst_articles (
                        article_id,
                        title,
                        slug,
                        content,
                        image,
                        status,
                        created_at
                    ) VALUES (
                        @id,
                        @title,
                        @slug,
                        @content,
                        @image,
                        @status,
                        GETDATE()
                    )";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", articleId);
                cmd.Parameters.AddWithValue("@title", title);
                cmd.Parameters.AddWithValue("@slug", slug);
                cmd.Parameters.AddWithValue("@content", content);
                cmd.Parameters.AddWithValue("@image", (object?)imagePath ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@status", "PUBLISHED");

                cmd.ExecuteNonQuery();

                // insert tags
                var lastTagCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(tag_id),'TGA0000') FROM mst_tag_articles",
                    conn
                );
                var lastTagId = lastTagCmd.ExecuteScalar()?.ToString() ?? "TGA0000";
                var currentTag = ExtractTrailingNumber(lastTagId);

                foreach (var code in tagCodes)
                {
                    currentTag++;
                    var tagId = "TGA" + currentTag.ToString("D4");

                    var tagSql = @"
                        INSERT INTO mst_tag_articles (
                            tag_id,
                            article_id,
                            tag_code,
                            created_at
                        ) VALUES (
                            @tid,
                            @aid,
                            @code,
                            GETDATE()
                        )";

                    using var tagCmd = new SqlCommand(tagSql, conn);
                    tagCmd.Parameters.AddWithValue("@tid", tagId);
                    tagCmd.Parameters.AddWithValue("@aid", articleId);
                    tagCmd.Parameters.AddWithValue("@code", code);

                    tagCmd.ExecuteNonQuery();
                }

                return Json(DTOResponse.ok(null, "article created"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        [RequestSizeLimit(50 * 1024 * 1024)] // 50MB
        public IActionResult Update(DTORequest req)
        {
            try
            {
                var f = Request.Form;
                var file = Request.Form.Files["image"];
                var articleId = f["article_id"].ToString();

                if (string.IsNullOrWhiteSpace(articleId))
                    return Json(DTOResponse.fail("invalid article id", 400));

                var title = f["title"].ToString();
                var content = f["content"].ToString();
                var rawTags = string.Join(",", f["tags"].ToArray());

                if (string.IsNullOrWhiteSpace(title))
                    return Json(DTOResponse.fail("title is required", 400));

                if (string.IsNullOrWhiteSpace(content))
                    return Json(DTOResponse.fail("content is required", 400));

                var tagCodes = rawTags
                    .Split(new[] { ',' }, StringSplitOptions.RemoveEmptyEntries)
                    .Select(t => t.Trim())
                    .Where(t => !string.IsNullOrWhiteSpace(t))
                    .Distinct(StringComparer.OrdinalIgnoreCase)
                    .ToList();

                if (tagCodes.Count == 0)
                    return Json(DTOResponse.fail("tags are required", 400));

                using var conn = GetConn();
                conn.Open();

                // we keep existing slug, do not change on update
                // upload new image if provided
                string imageSql = "";
                string imagePath = null;

                if (file != null && file.Length > 0)
                {
                    var folder = Path.Combine(
                        Directory.GetCurrentDirectory(),
                        "wwwroot/image/article"
                    );

                    if (!Directory.Exists(folder))
                        Directory.CreateDirectory(folder);

                    var fileName = articleId + Path.GetExtension(file.FileName);
                    var fullPath = Path.Combine(folder, fileName);

                    using var stream = new FileStream(fullPath, FileMode.Create);
                    file.CopyTo(stream);

                    imagePath = "/image/article/" + fileName;
                    imageSql = ", image=@image";
                }

                var sql = $@"
                    UPDATE mst_articles SET
                        title = @title,
                        content = @content,
                        updated_at = GETDATE()
                        {imageSql}
                    WHERE article_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", articleId);
                cmd.Parameters.AddWithValue("@title", title);
                cmd.Parameters.AddWithValue("@content", content);

                if (imagePath != null)
                    cmd.Parameters.AddWithValue("@image", imagePath);

                cmd.ExecuteNonQuery();

                // update tags: delete then re-insert
                var delTagCmd = new SqlCommand(
                    "DELETE FROM mst_tag_articles WHERE article_id=@id",
                    conn
                );
                delTagCmd.Parameters.AddWithValue("@id", articleId);
                delTagCmd.ExecuteNonQuery();

                var lastTagCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(tag_id),'TGA0000') FROM mst_tag_articles",
                    conn
                );
                var lastTagId = lastTagCmd.ExecuteScalar()?.ToString() ?? "TGA0000";
                var currentTag = ExtractTrailingNumber(lastTagId);

                foreach (var code in tagCodes)
                {
                    currentTag++;
                    var tagId = "TGA" + currentTag.ToString("D4");

                    var tagSql = @"
                        INSERT INTO mst_tag_articles (
                            tag_id,
                            article_id,
                            tag_code,
                            created_at
                        ) VALUES (
                            @tid,
                            @aid,
                            @code,
                            GETDATE()
                        )";

                    using var tagCmd = new SqlCommand(tagSql, conn);
                    tagCmd.Parameters.AddWithValue("@tid", tagId);
                    tagCmd.Parameters.AddWithValue("@aid", articleId);
                    tagCmd.Parameters.AddWithValue("@code", code);

                    tagCmd.ExecuteNonQuery();
                }

                return Json(DTOResponse.ok(null, "article updated"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Delete([FromBody] DTORequest req)
        {
            try
            {
                if (string.IsNullOrEmpty(req?.id))
                    return Json(DTOResponse.fail("invalid article id", 400));

                using var conn = GetConn();
                conn.Open();

                var cmd = new SqlCommand(
                    "DELETE FROM mst_articles WHERE article_id=@id",
                    conn
                );
                cmd.Parameters.AddWithValue("@id", req.id);

                cmd.ExecuteNonQuery();
                // mst_tag_articles rows deleted by ON DELETE CASCADE

                return Json(DTOResponse.ok(null, "article deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
