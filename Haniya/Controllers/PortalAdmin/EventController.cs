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
    public class EventController : Controller
    {
        private readonly IConfiguration _config;

        public EventController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
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

        /* ===================== PAGE ===================== */

        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/Event/Index.cshtml");
        }

        public IActionResult Create()
        {
            // load tag master
            return View("~/Views/PortalAdmin/Event/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.eventId = id;
            // load tag master
            return View("~/Views/PortalAdmin/Event/Edit.cshtml");
        }

        /* ===================== API ===================== */
        private (int draw, int start, int length, string searchValue, string orderColumn, string orderDir)
            ParseDataTablesQuery(string[] columns)
        {
            var q = Request.Query;
            int.TryParse(q["draw"], out var draw);
            if (draw <= 0) draw = 1;
            int.TryParse(q["start"], out var start);
            if (start < 0) start = 0;
            int.TryParse(q["length"], out var length);
            if (length <= 0) length = 10;
            var searchValue = q["search[value]"].ToString() ?? string.Empty;

            var orderColumn = "title";
            var orderDir = "ASC";

            var orderColIdxStr = q["order[0][column]"].ToString();
            if (int.TryParse(orderColIdxStr, out var orderColIdx))
            {
                if (orderColIdx >= 0 && orderColIdx < columns.Length)
                    orderColumn = columns[orderColIdx];
            }

            var dir = q["order[0][dir]"].ToString();
            if (!string.IsNullOrWhiteSpace(dir) &&
                (dir.Equals("asc", StringComparison.OrdinalIgnoreCase) ||
                 dir.Equals("desc", StringComparison.OrdinalIgnoreCase)))
            {
                orderDir = dir.ToUpper();
            }

            return (draw, start, length, searchValue, orderColumn, orderDir);
        }

        [HttpGet]
        public IActionResult GetAll()
        {
            try
            {
                var columns = new[]
                {
            "profile_photo",
            "event_name",
            "location",
            "status",
            "created_at"
        };

                var (draw, start, length, searchValue, orderColumn, orderDir) = ParseDataTablesQuery(columns);

                using var conn = GetConn();
                conn.Open();

                // Total records
                var totalCmd = new SqlCommand("SELECT COUNT(*) FROM mst_events", conn);
                var recordsTotal = (int)totalCmd.ExecuteScalar();

                // Filtered count
                string whereSearch = "";
                if (!string.IsNullOrWhiteSpace(searchValue))
                {
                    whereSearch = @" WHERE (
                event_name LIKE @search OR
                location LIKE @search OR
                status LIKE @search OR
                description LIKE @search
            )";
                }

                var filteredCmd = new SqlCommand("SELECT COUNT(*) FROM mst_events" + whereSearch, conn);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    filteredCmd.Parameters.AddWithValue("@search", $"%{searchValue}%");
                var recordsFiltered = (int)filteredCmd.ExecuteScalar();

                // Main data query with tags
                var sql = $@"
            SELECT 
                e.event_id,
                e.event_name,
                e.description,
                e.location,
                e.status,
                e.profile_photo,
                e.created_at,
                STRING_AGG(t.tag_code, ', ') AS tags
            FROM mst_events e
            LEFT JOIN mst_tag_events t ON t.event_id = e.event_id
            {whereSearch}
            GROUP BY 
                e.event_id,
                e.event_name,
                e.description,
                e.location,
                e.status,
                e.profile_photo,
                e.created_at
            ORDER BY {orderColumn} {orderDir}
            OFFSET @start ROWS FETCH NEXT @length ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    cmd.Parameters.AddWithValue("@search", $"%{searchValue}%");

                var list = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    list.Add(new
                    {
                        event_id = rd["event_id"],
                        event_name = rd["event_name"],
                        description = rd["description"]?.ToString() ?? "",
                        location = rd["location"],
                        status = rd["status"],
                        profile_photo = rd["profile_photo"],
                        created_at = rd["created_at"],
                        tags = rd["tags"]?.ToString() ?? ""
                    });
                }

                return Json(new
                {
                    draw,
                    recordsTotal,
                    recordsFiltered,
                    data = list
                });
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
                var sql = "SELECT * FROM mst_events WHERE event_id = @id";
                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);
                using var rd = cmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("Event not found", 404));

                var evt = new
                {
                    event_id = rd["event_id"]?.ToString(),
                    event_name = rd["event_name"]?.ToString(),
                    description = rd["description"]?.ToString(),
                    location = rd["location"]?.ToString(),
                    profile_photo = rd["profile_photo"]?.ToString(),
                    status = rd["status"]?.ToString()
                };

                rd.Close();

                // Tags
                var tags = new List<string>();
                var tagSql = "SELECT tag_code FROM mst_tag_events WHERE event_id = @id ORDER BY created_at";
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
                    evt.event_id,
                    evt.event_name,
                    evt.description,
                    evt.location,
                    evt.profile_photo,
                    evt.status,
                    tags = tags
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        /* ===================== API (Create/Update) ===================== */

        [HttpPost]
        public IActionResult Create(DTORequest req)
        {
            try
            {
                var f = Request.Form;
                var file = Request.Form.Files["profile_photo"];

                var eventName = f["event_name"].ToString();
                var location = f["location"].ToString();
                var description = f["description"].ToString();
                var rawTags = string.Join(",", f["tags"].ToArray());

                if (string.IsNullOrWhiteSpace(eventName))
                    return Json(DTOResponse.fail("event name is required", 400));

                // Parse tags
                var tagCodes = rawTags
                    .Split(new[] { ',' }, StringSplitOptions.RemoveEmptyEntries)
                    .Select(t => t.Trim())
                    .Where(t => !string.IsNullOrWhiteSpace(t))
                    .Distinct(StringComparer.OrdinalIgnoreCase)
                    .ToList();

                if (tagCodes.Count == 0)
                    return Json(DTOResponse.fail("tags are required", 400));

                // Status is always ACTIVE for new events
                var status = "ACTIVE";

                using var conn = GetConn();
                conn.Open();

                // ===== generate event_id =====
                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(event_id),'EVT0000') FROM mst_events",
                    conn
                );
                var lastId = lastCmd.ExecuteScalar()?.ToString() ?? "EVT0000";
                var next = ExtractTrailingNumber(lastId) + 1;
                var eventId = "EVT" + next.ToString("D4");

                // ===== upload photo =====
                string photoPath = null;
                if (file != null && file.Length > 0)
                {
                    var folder = Path.Combine(
                        Directory.GetCurrentDirectory(),
                        "wwwroot/image/event"
                    );

                    if (!Directory.Exists(folder))
                        Directory.CreateDirectory(folder);

                    var fileName = eventId + Path.GetExtension(file.FileName);
                    var fullPath = Path.Combine(folder, fileName);

                    using var stream = new FileStream(fullPath, FileMode.Create);
                    file.CopyTo(stream);

                    photoPath = "/image/event/" + fileName;
                }

                // ===== insert event =====
                var sql = @"
                    INSERT INTO mst_events (
                        event_id,
                        event_name,
                        description,
                        location,
                        status,
                        profile_photo,
                        created_at
                    ) VALUES (
                        @id,
                        @name,
                        @desc,
                        @loc,
                        @status,
                        @photo,
                        GETDATE()
                    )";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", eventId);
                cmd.Parameters.AddWithValue("@name", eventName);
                cmd.Parameters.AddWithValue("@desc", (object?)description ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@loc", (object?)location ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@status", status);
                cmd.Parameters.AddWithValue("@photo", (object?)photoPath ?? DBNull.Value);

                cmd.ExecuteNonQuery();

                // ===== insert tags =====
                if (tagCodes.Count > 0)
                {
                    var lastTagCmd = new SqlCommand(
                        "SELECT ISNULL(MAX(tag_id),'TGE0000') FROM mst_tag_events",
                        conn
                    );
                    var lastTagId = lastTagCmd.ExecuteScalar()?.ToString() ?? "TGE0000";
                    var currentTag = ExtractTrailingNumber(lastTagId);

                    foreach (var code in tagCodes)
                    {
                        currentTag++;
                        var tagId = "TGE" + currentTag.ToString("D4");

                        var tagSql = @"
                            INSERT INTO mst_tag_events (
                                tag_id,
                                event_id,
                                tag_code,
                                created_at
                            ) VALUES (
                                @tid,
                                @eid,
                                @code,
                                GETDATE()
                            )";

                        using var tagCmd = new SqlCommand(tagSql, conn);
                        tagCmd.Parameters.AddWithValue("@tid", tagId);
                        tagCmd.Parameters.AddWithValue("@eid", eventId);
                        tagCmd.Parameters.AddWithValue("@code", code);

                        tagCmd.ExecuteNonQuery();
                    }
                }

                return Json(DTOResponse.ok(null, "event created"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Update(DTORequest req)
        {
            try
            {
                var f = Request.Form;
                var file = Request.Form.Files["profile_photo"];
                var eventId = f["event_id"].ToString();

                if (string.IsNullOrWhiteSpace(eventId))
                    return Json(DTOResponse.fail("invalid event id", 400));

                var eventName = f["event_name"].ToString();
                var location = f["location"].ToString();
                var description = f["description"].ToString();
                var rawTags = string.Join(",", f["tags"].ToArray());

                if (string.IsNullOrWhiteSpace(eventName))
                    return Json(DTOResponse.fail("event name is required", 400));

                // Parse tags
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

                // ===== upload photo (optional) =====
                string photoSql = "";
                string photoPath = null;

                if (file != null && file.Length > 0)
                {
                    var folder = Path.Combine(
                        Directory.GetCurrentDirectory(),
                        "wwwroot/image/event"
                    );

                    if (!Directory.Exists(folder))
                        Directory.CreateDirectory(folder);

                    var fileName = eventId + Path.GetExtension(file.FileName);
                    var fullPath = Path.Combine(folder, fileName);

                    using var stream = new FileStream(fullPath, FileMode.Create);
                    file.CopyTo(stream);

                    photoPath = "/image/event/" + fileName;
                    photoSql = ", profile_photo=@photo";
                }

                var sql = $@"
                    UPDATE mst_events SET
                        event_name = @name,
                        description = @desc,
                        location = @loc,
                        updated_at = GETDATE()
                        {photoSql}
                    WHERE event_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", eventId);
                cmd.Parameters.AddWithValue("@name", eventName);
                cmd.Parameters.AddWithValue("@desc", (object?)description ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@loc", (object?)location ?? DBNull.Value);

                if (photoPath != null)
                    cmd.Parameters.AddWithValue("@photo", photoPath);

                cmd.ExecuteNonQuery();

                // ===== update tags: delete then re-insert =====
                var delTagCmd = new SqlCommand(
                    "DELETE FROM mst_tag_events WHERE event_id=@id",
                    conn
                );
                delTagCmd.Parameters.AddWithValue("@id", eventId);
                delTagCmd.ExecuteNonQuery();

                if (tagCodes.Count > 0)
                {
                    var lastTagCmd = new SqlCommand(
                        "SELECT ISNULL(MAX(tag_id),'TGE0000') FROM mst_tag_events",
                        conn
                    );
                    var lastTagId = lastTagCmd.ExecuteScalar()?.ToString() ?? "TGE0000";
                    var currentTag = ExtractTrailingNumber(lastTagId);

                    foreach (var code in tagCodes)
                    {
                        currentTag++;
                        var tagId = "TGE" + currentTag.ToString("D4");

                        var tagSql = @"
                            INSERT INTO mst_tag_events (
                                tag_id,
                                event_id,
                                tag_code,
                                created_at
                            ) VALUES (
                                @tid,
                                @eid,
                                @code,
                                GETDATE()
                            )";

                        using var tagCmd = new SqlCommand(tagSql, conn);
                        tagCmd.Parameters.AddWithValue("@tid", tagId);
                        tagCmd.Parameters.AddWithValue("@eid", eventId);
                        tagCmd.Parameters.AddWithValue("@code", code);

                        tagCmd.ExecuteNonQuery();
                    }
                }

                return Json(DTOResponse.ok(null, "event updated"));
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
                    return Json(DTOResponse.fail("invalid event id", 400));

                using var conn = GetConn();
                conn.Open();

                var cmd = new SqlCommand(
                    "DELETE FROM mst_events WHERE event_id=@id",
                    conn
                );
                cmd.Parameters.AddWithValue("@id", req.id);

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "event deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
