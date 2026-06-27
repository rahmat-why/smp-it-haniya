using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Globalization;
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

        private bool TryParseIsoDate(string value, out DateTime date)
        {
            return DateTime.TryParseExact(
                value,
                "yyyy-MM-dd",
                CultureInfo.InvariantCulture,
                DateTimeStyles.None,
                out date
            );
        }

        private HashSet<int> ParseHolidayClasses(string raw)
        {
            var result = new HashSet<int>();
            if (string.IsNullOrWhiteSpace(raw)) return result;

            foreach (var part in raw.Split(',', StringSplitOptions.RemoveEmptyEntries))
            {
                if (int.TryParse(part.Trim(), out var level) && (level == 7 || level == 8 || level == 9))
                {
                    result.Add(level);
                }
            }

            return result;
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
        public class ListSort
        {
            public string field { get; set; } = "eventDate";
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
                    ["eventName"] = "e.event_name",
                    ["location"] = "e.location",
                    ["status"] = "e.status",
                    ["description"] = "e.description",
                    ["tags"] = "STRING_AGG(t.tag_code, ', ')",
                    ["eventDate"] = "e.start_date"
                };
                var sort = req.sort ?? new ListSort();
                var orderColumn = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "e.start_date";
                var orderDir = string.Equals(sort.order, "asc", StringComparison.OrdinalIgnoreCase) ? "ASC" : "DESC";

                using var conn = GetConn();
                conn.Open();

                // Total records
                var totalCmd = new SqlCommand("SELECT COUNT(*) FROM mst_events", conn);
                var recordsTotal = (int)totalCmd.ExecuteScalar();

                // Filtered count
                string whereSearch = " WHERE 1=1 ";
                if (!string.IsNullOrWhiteSpace(searchValue))
                {
                    whereSearch += @" AND (
                event_name LIKE @search OR
                location LIKE @search OR
                status LIKE @search OR
                description LIKE @search
            )";
                }
                if (!string.IsNullOrWhiteSpace(statusFilter))
                    whereSearch += " AND status = @statusFilter ";

                var filteredCmd = new SqlCommand("SELECT COUNT(*) FROM mst_events" + whereSearch, conn);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    filteredCmd.Parameters.AddWithValue("@search", $"%{searchValue}%");
                if (!string.IsNullOrWhiteSpace(statusFilter))
                    filteredCmd.Parameters.AddWithValue("@statusFilter", statusFilter);
                var recordsFiltered = (int)filteredCmd.ExecuteScalar();

                var totalPages = recordsFiltered == 0 ? 1 : (int)Math.Ceiling(recordsFiltered / (double)limit);
                page = Math.Min(page, totalPages);
                var offset = (page - 1) * limit;

                // Main data query with tags
                var sql = $@"
            SELECT 
                e.event_id,
                e.event_name,
                e.description,
                e.location,
                e.status,
                e.profile_photo,
                CONCAT(
                    FORMAT(e.start_date, 'dd MMM yyyy'),
                    ' - ',
                    FORMAT(e.end_date, 'dd MMM yyyy')
                ) AS event_date,
                STRING_AGG(COALESCE(mdt.item_name, t.tag_code), ', ') AS tags
            FROM mst_events e
            LEFT JOIN mst_tag_events t ON t.event_id = e.event_id
            LEFT JOIN mst_detail_settings mdt ON mdt.item_code = t.tag_code AND mdt.header_id = 'TAG_EVENT'
            {whereSearch}
            GROUP BY 
                e.event_id,
                e.event_name,
                e.description,
                e.location,
                e.status,
                e.profile_photo,
                e.start_date,
                e.end_date
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
                        event_id = rd["event_id"],
                        event_name = rd["event_name"],
                        description = rd["description"]?.ToString() ?? "",
                        location = rd["location"],
                        status = rd["status"],
                        profile_photo = rd["profile_photo"],
                        event_date = rd["event_date"]?.ToString() ?? "-",
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
                    status = rd["status"]?.ToString(),
                    start_date = rd["start_date"] == DBNull.Value ? null : Convert.ToDateTime(rd["start_date"]).ToString("yyyy-MM-dd"),
                    end_date = rd["end_date"] == DBNull.Value ? null : Convert.ToDateTime(rd["end_date"]).ToString("yyyy-MM-dd")
                };

                rd.Close();

                // Tags
                var tags = new List<object>();
                var tagSql = @"
                    SELECT t.tag_code, mdt.item_name 
                    FROM mst_tag_events t
                    LEFT JOIN mst_detail_settings mdt ON mdt.item_code = t.tag_code AND mdt.header_id = 'TAG_EVENT'
                    WHERE t.event_id = @id ORDER BY t.created_at";
                using var tagCmd = new SqlCommand(tagSql, conn);
                tagCmd.Parameters.AddWithValue("@id", id);
                using var tagRd = tagCmd.ExecuteReader();
                while (tagRd.Read())
                {
                    var code = tagRd["tag_code"]?.ToString();
                    var name = tagRd["item_name"]?.ToString() ?? code;
                    if (!string.IsNullOrWhiteSpace(code))
                        tags.Add(new { id = code, text = name });
                }

                var holidayClasses = new List<string>();
                var classSql = "SELECT class_level, is_holiday FROM mst_event_classes WHERE event_id = @id";
                using var classCmd = new SqlCommand(classSql, conn);
                classCmd.Parameters.AddWithValue("@id", id);
                using var classRd = classCmd.ExecuteReader();
                while (classRd.Read())
                {
                    var classLevel = classRd["class_level"] != DBNull.Value ? Convert.ToInt32(classRd["class_level"]) : 0;
                    var isHoliday = classRd["is_holiday"] != DBNull.Value && Convert.ToInt32(classRd["is_holiday"]) == 1;
                    if (isHoliday && (classLevel == 7 || classLevel == 8 || classLevel == 9))
                    {
                        holidayClasses.Add(classLevel.ToString());
                    }
                }

                return Json(DTOResponse.ok(new
                {
                    evt.event_id,
                    evt.event_name,
                    evt.description,
                    evt.location,
                    evt.start_date,
                    evt.end_date,
                    evt.profile_photo,
                    evt.status,
                    tags = tags,
                    holiday_classes = holidayClasses
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
                var startDateRaw = f["start_date"].ToString();
                var endDateRaw = f["end_date"].ToString();
                var holidayRaw = f["class_holidays"].ToString();
                var description = f["description"].ToString();
                var rawTags = string.Join(",", f["tags"].ToArray());

                if (string.IsNullOrWhiteSpace(eventName))
                    return Json(DTOResponse.fail("event name is required", 400));
                if (string.IsNullOrWhiteSpace(startDateRaw))
                    return Json(DTOResponse.fail("start date is required", 400));
                if (string.IsNullOrWhiteSpace(endDateRaw))
                    return Json(DTOResponse.fail("end date is required", 400));
                if (!TryParseIsoDate(startDateRaw, out var startDate))
                    return Json(DTOResponse.fail("invalid start date format", 400));
                if (!TryParseIsoDate(endDateRaw, out var endDate))
                    return Json(DTOResponse.fail("invalid end date format", 400));
                if (endDate.Date < startDate.Date)
                    return Json(DTOResponse.fail("end date cannot be before start date", 400));

                var holidayClasses = ParseHolidayClasses(holidayRaw);

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
                using var tx = conn.BeginTransaction();

                // ===== generate event_id =====
                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(event_id),'EVT0000') FROM mst_events",
                    conn,
                    tx
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
                        start_date,
                        end_date,
                        status,
                        profile_photo,
                        created_at
                    ) VALUES (
                        @id,
                        @name,
                        @desc,
                        @loc,
                        @startDate,
                        @endDate,
                        @status,
                        @photo,
                        GETDATE()
                    )";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Transaction = tx;
                cmd.Parameters.AddWithValue("@id", eventId);
                cmd.Parameters.AddWithValue("@name", eventName);
                cmd.Parameters.AddWithValue("@desc", (object?)description ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@loc", (object?)location ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@status", status);
                cmd.Parameters.AddWithValue("@photo", (object?)photoPath ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@startDate", startDate.Date);
                cmd.Parameters.AddWithValue("@endDate", endDate.Date);

                cmd.ExecuteNonQuery();

                for (var level = 7; level <= 9; level++)
                {
                    var classSql = @"
                        INSERT INTO mst_event_classes (
                            event_id,
                            class_level,
                            is_holiday
                        ) VALUES (
                            @eid,
                            @level,
                            @holiday
                        )";

                    using var classCmd = new SqlCommand(classSql, conn, tx);
                    classCmd.Parameters.AddWithValue("@eid", eventId);
                    classCmd.Parameters.AddWithValue("@level", level);
                    classCmd.Parameters.AddWithValue("@holiday", holidayClasses.Contains(level) ? 1 : 0);
                    classCmd.ExecuteNonQuery();
                }

                // ===== insert tags =====
                if (tagCodes.Count > 0)
                {
                    var lastTagCmd = new SqlCommand(
                        "SELECT ISNULL(MAX(tag_id),'TGE0000') FROM mst_tag_events",
                        conn,
                        tx
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

                        using var tagCmd = new SqlCommand(tagSql, conn, tx);
                        tagCmd.Parameters.AddWithValue("@tid", tagId);
                        tagCmd.Parameters.AddWithValue("@eid", eventId);
                        tagCmd.Parameters.AddWithValue("@code", code);

                        tagCmd.ExecuteNonQuery();
                    }
                }

                tx.Commit();

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
                var startDateRaw = f["start_date"].ToString();
                var endDateRaw = f["end_date"].ToString();
                var holidayRaw = f["class_holidays"].ToString();
                var description = f["description"].ToString();
                var rawTags = string.Join(",", f["tags"].ToArray());

                if (string.IsNullOrWhiteSpace(eventName))
                    return Json(DTOResponse.fail("event name is required", 400));
                if (string.IsNullOrWhiteSpace(startDateRaw))
                    return Json(DTOResponse.fail("start date is required", 400));
                if (string.IsNullOrWhiteSpace(endDateRaw))
                    return Json(DTOResponse.fail("end date is required", 400));
                if (!TryParseIsoDate(startDateRaw, out var startDate))
                    return Json(DTOResponse.fail("invalid start date format", 400));
                if (!TryParseIsoDate(endDateRaw, out var endDate))
                    return Json(DTOResponse.fail("invalid end date format", 400));
                if (endDate.Date < startDate.Date)
                    return Json(DTOResponse.fail("end date cannot be before start date", 400));

                var holidayClasses = ParseHolidayClasses(holidayRaw);

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
                using var tx = conn.BeginTransaction();

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
                        start_date = @startDate,
                        end_date = @endDate,
                        updated_at = GETDATE()
                        {photoSql}
                    WHERE event_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Transaction = tx;
                cmd.Parameters.AddWithValue("@id", eventId);
                cmd.Parameters.AddWithValue("@name", eventName);
                cmd.Parameters.AddWithValue("@desc", (object?)description ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@loc", (object?)location ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@startDate", startDate.Date);
                cmd.Parameters.AddWithValue("@endDate", endDate.Date);

                if (photoPath != null)
                    cmd.Parameters.AddWithValue("@photo", photoPath);

                cmd.ExecuteNonQuery();

                var delClassCmd = new SqlCommand(
                    "DELETE FROM mst_event_classes WHERE event_id=@id",
                    conn,
                    tx
                );
                delClassCmd.Parameters.AddWithValue("@id", eventId);
                delClassCmd.ExecuteNonQuery();

                for (var level = 7; level <= 9; level++)
                {
                    var classSql = @"
                        INSERT INTO mst_event_classes (
                            event_id,
                            class_level,
                            is_holiday
                        ) VALUES (
                            @eid,
                            @level,
                            @holiday
                        )";

                    using var classCmd = new SqlCommand(classSql, conn, tx);
                    classCmd.Parameters.AddWithValue("@eid", eventId);
                    classCmd.Parameters.AddWithValue("@level", level);
                    classCmd.Parameters.AddWithValue("@holiday", holidayClasses.Contains(level) ? 1 : 0);
                    classCmd.ExecuteNonQuery();
                }

                // ===== update tags: delete then re-insert =====
                var delTagCmd = new SqlCommand(
                    "DELETE FROM mst_tag_events WHERE event_id=@id",
                    conn,
                    tx
                );
                delTagCmd.Parameters.AddWithValue("@id", eventId);
                delTagCmd.ExecuteNonQuery();

                if (tagCodes.Count > 0)
                {
                    var lastTagCmd = new SqlCommand(
                        "SELECT ISNULL(MAX(tag_id),'TGE0000') FROM mst_tag_events",
                        conn,
                        tx
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

                        using var tagCmd = new SqlCommand(tagSql, conn, tx);
                        tagCmd.Parameters.AddWithValue("@tid", tagId);
                        tagCmd.Parameters.AddWithValue("@eid", eventId);
                        tagCmd.Parameters.AddWithValue("@code", code);

                        tagCmd.ExecuteNonQuery();
                    }
                }

                tx.Commit();

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
                using var tx = conn.BeginTransaction();

                var delClass = new SqlCommand(
                    "DELETE FROM mst_event_classes WHERE event_id=@id",
                    conn,
                    tx
                );
                delClass.Parameters.AddWithValue("@id", req.id);
                delClass.ExecuteNonQuery();

                var delTag = new SqlCommand(
                    "DELETE FROM mst_tag_events WHERE event_id=@id",
                    conn,
                    tx
                );
                delTag.Parameters.AddWithValue("@id", req.id);
                delTag.ExecuteNonQuery();

                var cmd = new SqlCommand(
                    "DELETE FROM mst_events WHERE event_id=@id",
                    conn,
                    tx
                );
                cmd.Parameters.AddWithValue("@id", req.id);

                cmd.ExecuteNonQuery();
                tx.Commit();

                return Json(DTOResponse.ok(null, "event deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}

