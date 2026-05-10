using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Text.Json;
using Haniya.Models;
using System.Linq;
using System.Globalization;
using System.Text.RegularExpressions;
using Rotativa.AspNetCore;
using Rotativa.AspNetCore.Options;

namespace Haniya.Controllers.PortalAdmin
{
    public class PaymentController : Controller
    {
        private readonly IConfiguration _config;
        public PaymentController(IConfiguration config) => _config = config;

        private SqlConnection GetConn() => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        public IActionResult Index() => View("~/Views/PortalAdmin/Payment/Index.cshtml");
        public IActionResult Create() => View("~/Views/PortalAdmin/Payment/Create.cshtml");
        public IActionResult Edit(string id)
        {
            ViewBag.paymentId = id;
            return View("~/Views/PortalAdmin/Payment/Edit.cshtml");
        }
        public IActionResult Detail(string id)
        {
            ViewBag.paymentId = id;
            return View("~/Views/PortalAdmin/Payment/Detail.cshtml");
        }

        public class ListSort
        {
            public string field { get; set; } = "dueDate";
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
            req ??= new ListRequest();
            var page = req.page <= 0 ? 1 : req.page;
            var limit = req.limit <= 0 ? 10 : Math.Min(req.limit, 50);
            var offset = (page - 1) * limit;
            var take = limit + 1;

            var filters = req.filters ?? new Dictionary<string, string>();
            filters.TryGetValue("search", out var search);
            filters.TryGetValue("academicClassId", out var academicClassId);
            filters.TryGetValue("status", out var status);
            filters.TryGetValue("paymentType", out var paymentType);

            var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
            {
                ["dueDate"] = "p.due_date",
                ["student"] = "s.full_name",
                ["class"] = "c.class_name",
                ["type"] = "pt.item_desc",
                ["bill"] = "p.total_price",
                ["paid"] = "p.total_payment",
                ["remaining"] = "p.remaining_payment",
                ["status"] = "p.status"
            };
            var sort = req.sort ?? new ListSort();
            var orderBy = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "p.due_date";
            var orderDir = string.Equals(sort.order, "asc", StringComparison.OrdinalIgnoreCase) ? "ASC" : "DESC";

            using var conn = GetConn();
            conn.Open();

            var where = new List<string> { "1=1" };
            if (!string.IsNullOrWhiteSpace(academicClassId)) where.Add("sc.academic_class_id = @classId");
            if (!string.IsNullOrWhiteSpace(status)) where.Add("p.status = @status");
            if (!string.IsNullOrWhiteSpace(paymentType)) where.Add("p.payment_type = @paymentType");
            if (!string.IsNullOrWhiteSpace(search))
            {
                where.Add(@"(
                    COALESCE(s.full_name, CONCAT(s.first_name, ' ', s.last_name)) LIKE @searchPattern
                    OR s.nis LIKE @searchPattern
                )");
            }
            var whereSql = "WHERE " + string.Join(" AND ", where);

            var sql = @"
        SELECT 
            p.payment_id,
            p.student_class_id,
            p.payment_type,
            p.total_price,
            p.total_payment,
            p.remaining_payment,
            p.status,
            p.due_date,
            p.payment_method,
            s.student_id,
            COALESCE(s.full_name, CONCAT(s.first_name, ' ', s.last_name)) AS student_name,
            s.nis,
            s.profile_photo,
            c.class_name,
            COALESCE(pt.item_desc, p.payment_type) AS payment_type_desc,
            COALESCE(pm.item_desc, p.payment_method) AS payment_method_desc
        FROM txn_payments p
        LEFT JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
        LEFT JOIN mst_students s ON sc.student_id = s.student_id
        LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
        LEFT JOIN mst_classes c ON ac.class_id = c.class_id
        LEFT JOIN txn_payment_instalments i ON i.payment_id = p.payment_id
        LEFT JOIN mst_detail_settings pt ON p.payment_type = pt.detail_id AND pt.header_id = 'PAYMENT_TYPE'
        LEFT JOIN mst_detail_settings pm ON pm.header_id = 'PAYMENT_METHOD'
                                        AND (p.payment_method = pm.detail_id OR p.payment_method = pm.item_code)
        {WHERE_SQL}
        GROUP BY 
            p.payment_id, p.student_class_id, p.payment_type, p.payment_method,
            p.total_price, p.total_payment, p.remaining_payment,
            p.status, p.due_date, s.student_id, s.full_name, 
            s.first_name, s.last_name, s.nis, s.profile_photo, c.class_name,
            pt.item_desc, pm.item_desc
        ORDER BY {ORDER_BY} {ORDER_DIR}
        OFFSET @offset ROWS FETCH NEXT @take ROWS ONLY"
                .Replace("{WHERE_SQL}", whereSql)
                .Replace("{ORDER_BY}", orderBy)
                .Replace("{ORDER_DIR}", orderDir);

            var list = new List<object>();
            using (var cmd = new SqlCommand(sql, conn))
            {
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@take", take);
                if (!string.IsNullOrWhiteSpace(academicClassId)) cmd.Parameters.AddWithValue("@classId", academicClassId.Trim());
                if (!string.IsNullOrWhiteSpace(status)) cmd.Parameters.AddWithValue("@status", status.Trim());
                if (!string.IsNullOrWhiteSpace(paymentType)) cmd.Parameters.AddWithValue("@paymentType", paymentType.Trim());
                if (!string.IsNullOrWhiteSpace(search)) cmd.Parameters.AddWithValue("@searchPattern", $"%{search.Trim()}%");

                using var r = cmd.ExecuteReader();
                while (r.Read())
                {
                    list.Add(new
                    {
                        payment_id = r["payment_id"].ToString(),
                        student_class_id = r["student_class_id"].ToString(),
                        payment_type = r["payment_type_desc"]?.ToString(),
                        total_price = r["total_price"] as decimal?,
                        total_payment = r["total_payment"] as decimal?,
                        remaining_payment = r["remaining_payment"] as decimal?,
                        status = r["status"]?.ToString(),
                        due_date = r["due_date"] == DBNull.Value ? null : ((DateTime)r["due_date"]).ToString("yyyy-MM-dd"),
                        payment_method = r["payment_method_desc"]?.ToString(),
                        student_id = r["student_id"]?.ToString(),
                        student_name = r["student_name"]?.ToString(),
                        nis = r["nis"]?.ToString(),
                        profile_photo = r["profile_photo"]?.ToString() ?? "/image/no-image.png",
                        class_name = r["class_name"]?.ToString()
                    });
                }
            }

            var hasNextPage = list.Count > limit;
            if (hasNextPage) list = list.Take(limit).ToList();
            return Json(DTOResponse.ok(new { data = list, hasNextPage }));
        }

        [HttpGet]
        public IActionResult GetPublishFormData()
        {
            try
            {
                using var conn = GetConn();
                conn.Open();

                var paymentTypes = new List<object>();
                var paymentTypeSql = @"
                    SELECT detail_id, item_code, item_name, item_desc
                    FROM mst_detail_settings
                    WHERE header_id = 'PAYMENT_TYPE'
                      AND status = 'ACTIVE'
                    ORDER BY item_desc";

                using (var cmd = new SqlCommand(paymentTypeSql, conn))
                using (var rd = cmd.ExecuteReader())
                {
                    while (rd.Read())
                    {
                        var itemCode = rd["item_code"]?.ToString() ?? "";
                        var itemName = rd["item_name"]?.ToString() ?? "";
                        TryParseMoney(itemName, out var amount);
                        var dueDate = CalculateDueDateForNextMonth(itemCode, DateTime.Now);

                        paymentTypes.Add(new
                        {
                            id = rd["detail_id"]?.ToString(),
                            text = rd["item_desc"]?.ToString(),
                            item_code = itemCode,
                            amount,
                            due_date = dueDate.ToString("yyyy-MM-dd")
                        });
                    }
                }

                var classes = new List<object>();
                var classSql = @"
                    SELECT DISTINCT mac.academic_class_id, mc.class_name
                    FROM mst_academic_classes mac
                    JOIN mst_classes mc
                        ON mac.class_id = mc.class_id
                    JOIN mst_academic_years may
                        ON mac.academic_year_id = may.academic_year_id
                    WHERE may.status = 'ACTIVE'
                    ORDER BY mc.class_name";

                using (var cmd = new SqlCommand(classSql, conn))
                using (var rd = cmd.ExecuteReader())
                {
                    while (rd.Read())
                    {
                        classes.Add(new
                        {
                            id = rd["academic_class_id"]?.ToString(),
                            class_name = rd["class_name"]?.ToString()
                        });
                    }
                }

                return Json(DTOResponse.ok(new { paymentTypes, classes }));
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
                if (string.IsNullOrWhiteSpace(id)) return Json(DTOResponse.fail("Invalid ID", 400));

                using var conn = GetConn();
                conn.Open();

                var headerSql = @"
            SELECT 
                p.payment_id,
                p.student_class_id,
                p.payment_type,
                p.total_price,
                p.total_payment,
                p.remaining_payment,
                p.status,
                p.due_date,
                p.payment_method,
                COALESCE(s.full_name, CONCAT(s.first_name, ' ', s.last_name)) AS student_name,
                s.nis,
                c.class_name,
                COALESCE(pt.item_desc, p.payment_type) AS payment_type_desc,
                COALESCE(pm.item_desc, p.payment_method) AS payment_method_desc
            FROM txn_payments p
            LEFT JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
            LEFT JOIN mst_students s ON sc.student_id = s.student_id
            LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c ON ac.class_id = c.class_id
            LEFT JOIN mst_detail_settings pt ON p.payment_type = pt.detail_id AND pt.header_id = 'PAYMENT_TYPE'
            LEFT JOIN mst_detail_settings pm ON pm.header_id = 'PAYMENT_METHOD'
                                            AND (p.payment_method = pm.detail_id OR p.payment_method = pm.item_code)
            WHERE p.payment_id = @id";

                dynamic header = null;
                using (var cmd = new SqlCommand(headerSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var r = cmd.ExecuteReader();
                    if (r.Read())
                    {
                        header = new
                        {
                            payment_id = r["payment_id"].ToString(),
                            student_class_id = r["student_class_id"].ToString(),
                            payment_type = r["payment_type"]?.ToString(),
                            total_price = r["total_price"] as decimal?,
                            total_payment = r["total_payment"] as decimal?,
                            remaining_payment = r["remaining_payment"] as decimal?,
                            status = r["status"]?.ToString(),
                            due_date = r["due_date"] == DBNull.Value ? null : ((DateTime)r["due_date"]).ToString("yyyy-MM-dd"),
                            payment_method = r["payment_method"]?.ToString(),
                            // Data tambahan untuk dropdown Edit
                            student_name = r["student_name"]?.ToString(),
                            nis = r["nis"]?.ToString(),
                            class_name = r["class_name"]?.ToString(),
                            payment_type_desc = r["payment_type_desc"]?.ToString(),
                            payment_method_desc = r["payment_method_desc"]?.ToString()
                        };
                    }
                }

                if (header == null) return Json(DTOResponse.fail("Not found", 404));

                var instalments = new List<object>();
                var instSql = @"
            SELECT i.instalment_id, i.instalment_number, i.total_payment, 
                   i.payment_date, i.notes, i.method,
                   COALESCE(pm.item_name, i.method) AS payment_method_desc
            FROM txn_payment_instalments i
            LEFT JOIN mst_detail_settings pm
                ON pm.header_id = 'PAYMENT_METHOD'
               AND pm.item_code = i.method
            WHERE i.payment_id = @id
            ORDER BY i.instalment_number";

                using (var cmd = new SqlCommand(instSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var r = cmd.ExecuteReader();
                    while (r.Read())
                    {
                        instalments.Add(new
                        {
                            instalment_id = r["instalment_id"]?.ToString(),
                            instalment_number = Convert.ToInt32(r["instalment_number"]),
                            total_payment = r["total_payment"] as decimal?,
                            payment_date = r["payment_date"] == DBNull.Value ? null : ((DateTime)r["payment_date"]).ToString("yyyy-MM-dd"),
                            notes = r["notes"]?.ToString(),
                            payment_method = r["method"]?.ToString(),
                            payment_method_desc = r["payment_method_desc"]?.ToString()
                        });
                    }
                }

                return Json(DTOResponse.ok(new
                {
                    header.payment_id,
                    header.student_class_id,
                    header.payment_type,
                    header.total_price,
                    header.total_payment,
                    header.remaining_payment,
                    header.status,
                    header.due_date,
                    header.payment_method,
                    header.student_name,
                    header.nis,
                    header.class_name,
                    header.payment_type_desc,
                    header.payment_method_desc,
                    instalments
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Create(DTORequest req)
        {
            try
            {
                var f = Request.Form;
                var payment_type = f["payment_type"].ToString();
                var selectedClassesRaw = f["academic_class_ids"].ToString();

                if (string.IsNullOrWhiteSpace(payment_type)) return Json(DTOResponse.fail("Payment type is required", 400));
                if (string.IsNullOrWhiteSpace(selectedClassesRaw)) return Json(DTOResponse.fail("At least one class is required", 400));

                var selectedAcademicClassIds = selectedClassesRaw
                    .Split(',', StringSplitOptions.RemoveEmptyEntries)
                    .Select(x => x.Trim())
                    .Where(x => !string.IsNullOrWhiteSpace(x))
                    .Distinct(StringComparer.OrdinalIgnoreCase)
                    .ToList();

                if (selectedAcademicClassIds.Count == 0) return Json(DTOResponse.fail("At least one class is required", 400));

                using var conn = GetConn();
                conn.Open();
                using var tx = conn.BeginTransaction();

                var paymentTypeSql = @"
                    SELECT item_code, item_name
                    FROM mst_detail_settings
                    WHERE detail_id = @detailId
                      AND header_id = 'PAYMENT_TYPE'
                      AND status = 'ACTIVE'";

                string itemCode = null;
                string itemName = null;
                using (var cmd = new SqlCommand(paymentTypeSql, conn, tx))
                {
                    cmd.Parameters.AddWithValue("@detailId", payment_type);
                    using var rd = cmd.ExecuteReader();
                    if (!rd.Read())
                    {
                        tx.Rollback();
                        return Json(DTOResponse.fail("Payment type not found", 404));
                    }

                    itemCode = rd["item_code"]?.ToString();
                    itemName = rd["item_name"]?.ToString();
                }

                if (!TryParseMoney(itemName, out var totalPrice) || totalPrice <= 0)
                {
                    tx.Rollback();
                    return Json(DTOResponse.fail("Invalid total price in payment type setting", 400));
                }

                var dueDate = CalculateDueDateForNextMonth(itemCode, DateTime.Now);

                var monthStart = new DateTime(DateTime.Now.Year, DateTime.Now.Month, 1);
                var nextMonthStart = monthStart.AddMonths(1);

                var checkPublishSql = @"
                    SELECT TOP 1 payment_id
                    FROM txn_payments
                    WHERE payment_type = @ptype
                      AND created_at >= @startMonth
                      AND created_at < @nextMonth";

                using (var cmd = new SqlCommand(checkPublishSql, conn, tx))
                {
                    cmd.Parameters.AddWithValue("@ptype", payment_type);
                    cmd.Parameters.AddWithValue("@startMonth", monthStart);
                    cmd.Parameters.AddWithValue("@nextMonth", nextMonthStart);

                    var existingId = cmd.ExecuteScalar()?.ToString();
                    if (!string.IsNullOrWhiteSpace(existingId))
                    {
                        tx.Rollback();
                        return Json(DTOResponse.fail("Payment type already published in this month", 409));
                    }
                }

                var classParamNames = selectedAcademicClassIds.Select((_, i) => $"@cls{i}").ToList();
                var studentClassSql = $@"
                    SELECT msc.student_class_id
                    FROM mst_student_classes msc
                    JOIN mst_academic_classes mac
                        ON msc.academic_class_id = mac.academic_class_id
                    JOIN mst_academic_years may
                        ON mac.academic_year_id = may.academic_year_id
                    WHERE may.status = 'ACTIVE'
                      AND msc.academic_class_id IN ({string.Join(",", classParamNames)})";

                var studentClassIds = new List<string>();
                using (var cmd = new SqlCommand(studentClassSql, conn, tx))
                {
                    for (int i = 0; i < selectedAcademicClassIds.Count; i++)
                    {
                        cmd.Parameters.AddWithValue(classParamNames[i], selectedAcademicClassIds[i]);
                    }

                    using var rd = cmd.ExecuteReader();
                    while (rd.Read())
                    {
                        var studentClassId = rd["student_class_id"]?.ToString();
                        if (!string.IsNullOrWhiteSpace(studentClassId))
                        {
                            studentClassIds.Add(studentClassId);
                        }
                    }
                }

                if (studentClassIds.Count == 0)
                {
                    tx.Rollback();
                    return Json(DTOResponse.fail("No active students found for selected classes", 400));
                }

                var seqCmd = new SqlCommand("SELECT ISNULL(MAX(payment_id),'PAY0000') FROM txn_payments", conn, tx);
                var seqRaw = seqCmd.ExecuteScalar()?.ToString() ?? "PAY0000";
                var seqNumText = seqRaw.Length > 3 ? seqRaw.Substring(3) : "0";
                var seq = int.TryParse(seqNumText, out var parsedSeq) ? parsedSeq : 0;

                var insertSql = @"
                    INSERT INTO txn_payments (
                        payment_id, student_class_id, payment_type, total_price,
                        total_payment, remaining_payment, status,
                        payment_date, due_date, payment_method, created_at, created_by
                    ) VALUES (
                        @id, @scid, @ptype, @tprice, @tpaid, @rem, @stat,
                        @pdate, @dueDate, @pmeth, GETDATE(), @by
                    )";

                var employeeIdClaim = User?.FindFirst("EmployeeId")?.Value;
                var createdBy = string.IsNullOrWhiteSpace(employeeIdClaim)
                    ? (object)DBNull.Value
                    : employeeIdClaim;

                foreach (var studentClassId in studentClassIds)
                {
                    seq++;
                    var paymentId = "PAY" + seq.ToString("D4");

                    using var cmd = new SqlCommand(insertSql, conn, tx);
                    cmd.Parameters.AddWithValue("@id", paymentId);
                    cmd.Parameters.AddWithValue("@scid", studentClassId);
                    cmd.Parameters.AddWithValue("@ptype", payment_type);
                    cmd.Parameters.AddWithValue("@tprice", totalPrice);
                    cmd.Parameters.AddWithValue("@tpaid", 0m);
                    cmd.Parameters.AddWithValue("@rem", totalPrice);
                    cmd.Parameters.AddWithValue("@stat", "UNPAID");
                    cmd.Parameters.AddWithValue("@pdate", dueDate);
                    cmd.Parameters.AddWithValue("@dueDate", dueDate);
                    cmd.Parameters.AddWithValue("@pmeth", DBNull.Value);
                    cmd.Parameters.AddWithValue("@by", createdBy);
                    cmd.ExecuteNonQuery();
                }

                tx.Commit();
                return Json(DTOResponse.ok(new
                {
                    published_student_count = studentClassIds.Count,
                    payment_type,
                    payment_date = dueDate.ToString("yyyy-MM-dd"),
                    due_date = dueDate.ToString("yyyy-MM-dd")
                }, "Payment published"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpPost]
        public IActionResult Update()
        {
            try
            {
                var f = Request.Form;
                var payment_id = f["payment_id"].ToString();
                var rawInstalments = f["instalments"].ToString();

                if (string.IsNullOrWhiteSpace(payment_id)) return Json(DTOResponse.fail("Invalid payment ID", 400));

                using var conn = GetConn();
                conn.Open();

                var headerCmd = new SqlCommand(@"
                    SELECT status, student_class_id, payment_type, total_price, payment_date, total_payment, remaining_payment
                    FROM txn_payments
                    WHERE payment_id = @id", conn);
                headerCmd.Parameters.AddWithValue("@id", payment_id);

                string existingStatus = null;
                string student_class_id = null;
                string payment_type = null;
                decimal totalPrice = 0m;
                DateTime paymentDate = DateTime.Now.Date;
                decimal existingPaid = 0m;
                decimal existingRemaining = 0m;

                using (var rd = headerCmd.ExecuteReader())
                {
                    if (!rd.Read())
                        return Json(DTOResponse.fail("Payment not found", 404));

                    existingStatus = rd["status"]?.ToString();
                    student_class_id = rd["student_class_id"]?.ToString();
                    payment_type = rd["payment_type"]?.ToString();
                    totalPrice = rd["total_price"] as decimal? ?? 0m;
                    if (rd["payment_date"] != DBNull.Value)
                        paymentDate = ((DateTime)rd["payment_date"]).Date;
                    existingPaid = rd["total_payment"] as decimal? ?? 0m;
                    existingRemaining = rd["remaining_payment"] as decimal? ?? 0m;
                }

                if (string.Equals(existingStatus, "PAID", StringComparison.OrdinalIgnoreCase))
                    return Json(DTOResponse.fail("Payment already fully paid", 409));

                if (string.IsNullOrWhiteSpace(student_class_id) || string.IsNullOrWhiteSpace(payment_type) || totalPrice <= 0)
                    return Json(DTOResponse.fail("Invalid payment header data", 400));

                var instalments = new List<dynamic>();
                if (!string.IsNullOrWhiteSpace(rawInstalments))
                {
                    using var json = JsonDocument.Parse(rawInstalments);
                    foreach (var elem in json.RootElement.EnumerateArray())
                    {
                        instalments.Add(new
                        {
                            total_payment = elem.TryGetProperty("total_payment", out var t) ? t.GetString() : "",
                            payment_date = elem.TryGetProperty("payment_date", out var d) ? d.GetString() : "",
                            notes = elem.TryGetProperty("notes", out var n) ? n.GetString() : "",
                            payment_method = elem.TryGetProperty("payment_method", out var pm) ? pm.GetString() : ""
                        });
                    }
                }

                var validInsts = new List<(decimal Amount, DateTime Date, string Notes, string PaymentMethod)>();
                foreach (var item in instalments)
                {
                    var amtStr = (string)item.total_payment;
                    var dateStr = (string)item.payment_date;
                    var note = (string)item.notes ?? "";
                    var paymentMethodCode = (string)item.payment_method ?? "";

                    if (string.IsNullOrWhiteSpace(amtStr) && string.IsNullOrWhiteSpace(dateStr)) continue;

                    if (!decimal.TryParse(amtStr, out decimal amt) || amt <= 0)
                        return Json(DTOResponse.fail("Invalid instalment amount", 400));

                    if (!DateTime.TryParse(dateStr, out DateTime idate))
                        return Json(DTOResponse.fail("Invalid instalment date", 400));

                    if (string.IsNullOrWhiteSpace(paymentMethodCode))
                        return Json(DTOResponse.fail("Payment method is required for each payment row", 400));

                    validInsts.Add((amt, idate.Date, note, paymentMethodCode));
                }

                if (validInsts.Count == 0)
                    return Json(DTOResponse.fail("Please input payment amount", 400));

                decimal newlyPaid = validInsts.Sum(x => x.Amount);
                if (newlyPaid > existingRemaining)
                    return Json(DTOResponse.fail("Payment amount cannot exceed remaining payment", 400));

                decimal paid = existingPaid + newlyPaid;
                decimal remaining = totalPrice - paid;
                if (remaining < 0) remaining = 0;
                string status = paid <= 0 ? "UNPAID" : remaining > 0 ? "PARTIAL" : "PAID";

                using var tx = conn.BeginTransaction();

                var headerSql = @"
                    UPDATE txn_payments SET
                        student_class_id = @scid,
                        payment_type = @ptype,
                        total_price = @tprice,
                        total_payment = @tpaid,
                        remaining_payment = @rem,
                        status = @stat,
                        payment_date = @pdate,
                        payment_method = @pmeth,
                        updated_at = GETDATE()
                    WHERE payment_id = @id";

                using (var cmd = new SqlCommand(headerSql, conn, tx))
                {
                    cmd.Parameters.AddWithValue("@id", payment_id);
                    cmd.Parameters.AddWithValue("@scid", student_class_id);
                    cmd.Parameters.AddWithValue("@ptype", payment_type);
                    cmd.Parameters.AddWithValue("@tprice", totalPrice);
                    cmd.Parameters.AddWithValue("@tpaid", paid);
                    cmd.Parameters.AddWithValue("@rem", remaining);
                    cmd.Parameters.AddWithValue("@stat", status);
                    cmd.Parameters.AddWithValue("@pdate", paymentDate);
                    cmd.Parameters.AddWithValue("@pmeth", validInsts.Last().PaymentMethod);
                    cmd.ExecuteNonQuery();
                }

                var instSeqCmd = new SqlCommand("SELECT ISNULL(MAX(instalment_id),'INS0000') FROM txn_payment_instalments", conn, tx);
                var instSeqRaw = instSeqCmd.ExecuteScalar()?.ToString() ?? "INS0000";
                var instSeqNumText = instSeqRaw.Length > 3 ? instSeqRaw.Substring(3) : "0";
                var instSeq = int.TryParse(instSeqNumText, out var parsedInstSeq) ? parsedInstSeq : 0;

                var numberCmd = new SqlCommand("SELECT ISNULL(MAX(instalment_number),0) FROM txn_payment_instalments WHERE payment_id = @id", conn, tx);
                numberCmd.Parameters.AddWithValue("@id", payment_id);
                var number = Convert.ToInt32(numberCmd.ExecuteScalar() ?? 0);

                foreach (var inst in validInsts)
                {
                    instSeq++;
                    number++;
                    var instId = "INS" + instSeq.ToString("D4");

                    var detSql = @"
                        INSERT INTO txn_payment_instalments (
                            instalment_id, payment_id, instalment_number,
                            total_payment, payment_date, method, notes, created_at, created_by
                        ) VALUES (
                            @iid, @pid, @num, @amt, @date, @pmethod, @note, GETDATE(), @by
                        )";

                    using var dcmd = new SqlCommand(detSql, conn, tx);
                    dcmd.Parameters.AddWithValue("@iid", instId);
                    dcmd.Parameters.AddWithValue("@pid", payment_id);
                    dcmd.Parameters.AddWithValue("@num", number);
                    dcmd.Parameters.AddWithValue("@amt", inst.Amount);
                    dcmd.Parameters.AddWithValue("@date", inst.Date);
                    dcmd.Parameters.AddWithValue("@pmethod", inst.PaymentMethod);
                    dcmd.Parameters.AddWithValue("@note", (object)inst.Notes ?? DBNull.Value);
                    dcmd.Parameters.AddWithValue("@by", DBNull.Value);
                    dcmd.ExecuteNonQuery();
                }

                tx.Commit();
                return Json(DTOResponse.ok(null, "Payment updated"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpGet]
        public IActionResult GetPaymentMethodItems()
        {
            try
            {
                using var conn = GetConn();
                conn.Open();

                var sql = @"
                    SELECT item_code, item_name
                    FROM mst_detail_settings
                    WHERE header_id = 'PAYMENT_METHOD'
                      AND status = 'ACTIVE'
                    ORDER BY item_name";

                var items = new List<object>();
                using var cmd = new SqlCommand(sql, conn);
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    items.Add(new
                    {
                        value = rd["item_code"]?.ToString(),
                        text = rd["item_name"]?.ToString()
                    });
                }

                return Json(DTOResponse.ok(items));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult GetTotalPriceByPaymentType(string paymentTypeId)
        {
            if (string.IsNullOrWhiteSpace(paymentTypeId))
            {
                return Json(new { success = false, message = "Payment type ID required", amount = 0m, due_date = "" });
            }

            try
            {
                using var conn = GetConn();
                conn.Open();

                var sql = @"
            SELECT item_name, item_code
            FROM mst_detail_settings
            WHERE detail_id = @detailId
              AND header_id = 'PAYMENT_TYPE'
              AND status = 'ACTIVE'";

                string itemName = null;
                string itemCode = null;
                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@detailId", paymentTypeId);
                    using var rd = cmd.ExecuteReader();
                    if (rd.Read())
                    {
                        itemName = rd["item_name"]?.ToString();
                        itemCode = rd["item_code"]?.ToString();
                    }
                }

                if (string.IsNullOrWhiteSpace(itemName))
                {
                    return Json(new { success = false, message = "Price not found for this payment type", amount = 0m, due_date = "" });
                }

                if (TryParseMoney(itemName, out decimal amount))
                {
                    var dueDate = CalculateDueDateForNextMonth(itemCode, DateTime.Now);
                    return Json(new { success = true, amount, due_date = dueDate.ToString("yyyy-MM-dd") });
                }

                return Json(new { success = false, message = "Invalid price format in database", amount = 0m, due_date = "" });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message, amount = 0m, due_date = "" });
            }
        }

        [HttpGet]
        public IActionResult ExportPaymentReceipt(string id, bool download = true)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(id))
                    return Content("ID pembayaran tidak valid");

                var receipt = BuildPaymentReceipt(id);
                if (receipt == null)
                    return Content("Data pembayaran tidak ditemukan");

                var model = new ReceiptExportViewModel
                {
                    Receipts = new List<ReceiptItemViewModel> { receipt },
                    RenderMode = "payment"
                };

                var pdf = new ViewAsPdf("~/Views/PortalAdmin/Payment/ReceiptPdf.cshtml", model)
                {
                    PageSize = Size.A4,
                    PageOrientation = Orientation.Portrait,
                    CustomSwitches = "--print-media-type --disable-smart-shrinking --margin-top 6mm --margin-bottom 6mm --margin-left 6mm --margin-right 6mm"
                };

                if (download)
                    pdf.FileName = $"Kwitansi-Pembayaran-{id}.pdf";

                return pdf;
            }
            catch (Exception ex)
            {
                return Content($"Gagal export kwitansi pembayaran: {ex.Message}");
            }
        }

        [HttpGet]
        public IActionResult ExportInstalmentReceipt(string paymentId, string? instalmentId = null, bool download = true)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(paymentId))
                    return Content("ID pembayaran tidak valid");

                var receipts = BuildInstalmentReceipts(paymentId, instalmentId);
                if (receipts.Count == 0)
                    return Content("Data cicilan tidak ditemukan");

                var model = new ReceiptExportViewModel
                {
                    Receipts = receipts,
                    RenderMode = "instalment"
                };

                var pdf = new ViewAsPdf("~/Views/PortalAdmin/Payment/ReceiptPdf.cshtml", model)
                {
                    PageSize = Size.A4,
                    PageOrientation = Orientation.Portrait,
                    CustomSwitches = "--print-media-type --disable-smart-shrinking --margin-top 6mm --margin-bottom 6mm --margin-left 6mm --margin-right 6mm"
                };

                if (download)
                {
                    pdf.FileName = string.IsNullOrWhiteSpace(instalmentId)
                        ? $"Kwitansi-Cicilan-{paymentId}.pdf"
                        : $"Kwitansi-Cicilan-{instalmentId}.pdf";
                }

                return pdf;
            }
            catch (Exception ex)
            {
                return Content($"Gagal export kwitansi cicilan: {ex.Message}");
            }
        }

        private ReceiptItemViewModel? BuildPaymentReceipt(string paymentId)
        {
            using var conn = GetConn();
            conn.Open();

            var sql = @"
                SELECT
                    p.payment_id,
                    p.total_payment,
                    p.remaining_payment,
                    p.payment_type,
                    COALESCE(s.full_name, CONCAT(s.first_name, ' ', s.last_name)) AS student_name,
                    c.class_name,
                    COALESCE(pt.item_desc, p.payment_type) AS payment_type_desc
                FROM txn_payments p
                LEFT JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                LEFT JOIN mst_students s ON sc.student_id = s.student_id
                LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                LEFT JOIN mst_classes c ON ac.class_id = c.class_id
                LEFT JOIN mst_detail_settings pt ON p.payment_type = pt.detail_id AND pt.header_id = 'PAYMENT_TYPE'
                WHERE p.payment_id = @id";

            using var cmd = new SqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@id", paymentId);
            using var rd = cmd.ExecuteReader();
            if (!rd.Read()) return null;

            var total = rd["total_payment"] as decimal? ?? 0m;
            var remaining = rd["remaining_payment"] as decimal? ?? 0m;
            var studentName = rd["student_name"]?.ToString() ?? "-";
            var className = rd["class_name"]?.ToString() ?? "-";
            var paymentTypeDesc = rd["payment_type_desc"]?.ToString() ?? rd["payment_type"]?.ToString() ?? "-";
            rd.Close();

            var paidDate = GetLatestInstalmentDate(conn, paymentId) ?? DateTime.Now.Date;
            return new ReceiptItemViewModel
            {
                PaymentId = paymentId,
                InstalmentId = null,
                StudentName = studentName,
                ClassName = className,
                PaymentDate = paidDate,
                TotalPayment = total,
                PaymentTypeDescription = paymentTypeDesc,
                Terbilang = ToTerbilang(total),
                KeteranganText = "PELUNASAN AKHIRUSANAH",
                PaymentLines = new List<string> { paymentTypeDesc }
            };
        }

        private List<ReceiptItemViewModel> BuildInstalmentReceipts(string paymentId, string? instalmentId)
        {
            using var conn = GetConn();
            conn.Open();

            var sql = @"
                SELECT
                    i.instalment_id,
                    i.total_payment,
                    i.payment_date,
                    i.notes,
                    p.payment_id,
                    p.payment_type,
                    COALESCE(s.full_name, CONCAT(s.first_name, ' ', s.last_name)) AS student_name,
                    c.class_name,
                    COALESCE(pt.item_desc, p.payment_type) AS payment_type_desc
                FROM txn_payment_instalments i
                INNER JOIN txn_payments p ON i.payment_id = p.payment_id
                LEFT JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                LEFT JOIN mst_students s ON sc.student_id = s.student_id
                LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                LEFT JOIN mst_classes c ON ac.class_id = c.class_id
                LEFT JOIN mst_detail_settings pt ON p.payment_type = pt.detail_id AND pt.header_id = 'PAYMENT_TYPE'
                WHERE i.payment_id = @paymentId
                  AND (@instalmentId IS NULL OR i.instalment_id = @instalmentId)
                ORDER BY i.instalment_number";

            var result = new List<ReceiptItemViewModel>();
            using var cmd = new SqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@paymentId", paymentId);
            cmd.Parameters.AddWithValue("@instalmentId", string.IsNullOrWhiteSpace(instalmentId) ? DBNull.Value : instalmentId);
            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                var amount = rd["total_payment"] as decimal? ?? 0m;
                var paymentTypeDesc = rd["payment_type_desc"]?.ToString() ?? rd["payment_type"]?.ToString() ?? "-";
                var notes = rd["notes"]?.ToString();
                result.Add(new ReceiptItemViewModel
                {
                    PaymentId = rd["payment_id"]?.ToString() ?? paymentId,
                    InstalmentId = rd["instalment_id"]?.ToString(),
                    StudentName = rd["student_name"]?.ToString() ?? "-",
                    ClassName = rd["class_name"]?.ToString() ?? "-",
                    PaymentDate = rd["payment_date"] == DBNull.Value ? DateTime.Now.Date : ((DateTime)rd["payment_date"]).Date,
                    TotalPayment = amount,
                    PaymentTypeDescription = paymentTypeDesc,
                    Terbilang = ToTerbilang(amount),
                    KeteranganText = string.IsNullOrWhiteSpace(notes) ? "CICILAN AKHIRUSANAH" : notes.Trim(),
                    PaymentLines = new List<string> { paymentTypeDesc }
                });
            }

            return result;
        }

        private DateTime? GetLatestInstalmentDate(SqlConnection conn, string paymentId)
        {
            var cmd = new SqlCommand("SELECT MAX(payment_date) FROM txn_payment_instalments WHERE payment_id = @id", conn);
            cmd.Parameters.AddWithValue("@id", paymentId);
            var raw = cmd.ExecuteScalar();
            if (raw == null || raw == DBNull.Value) return null;
            return Convert.ToDateTime(raw).Date;
        }

        private static string ToTerbilang(decimal amount)
        {
            var number = (long)Math.Floor(amount);
            if (number == 0) return "Nol Rupiah";
            return $"{ToTerbilangInt(number).Trim()} Rupiah";
        }

        private static string ToTerbilangInt(long value)
        {
            string[] angka = { "", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas" };
            if (value < 12) return " " + angka[value];
            if (value < 20) return ToTerbilangInt(value - 10) + " Belas";
            if (value < 100) return ToTerbilangInt(value / 10) + " Puluh" + ToTerbilangInt(value % 10);
            if (value < 200) return " Seratus" + ToTerbilangInt(value - 100);
            if (value < 1000) return ToTerbilangInt(value / 100) + " Ratus" + ToTerbilangInt(value % 100);
            if (value < 2000) return " Seribu" + ToTerbilangInt(value - 1000);
            if (value < 1000000) return ToTerbilangInt(value / 1000) + " Ribu" + ToTerbilangInt(value % 1000);
            if (value < 1000000000) return ToTerbilangInt(value / 1000000) + " Juta" + ToTerbilangInt(value % 1000000);
            if (value < 1000000000000) return ToTerbilangInt(value / 1000000000) + " Milyar" + ToTerbilangInt(value % 1000000000);
            return ToTerbilangInt(value / 1000000000000) + " Triliun" + ToTerbilangInt(value % 1000000000000);
        }

        public class ReceiptExportViewModel
        {
            public string RenderMode { get; set; } = "";
            public List<ReceiptItemViewModel> Receipts { get; set; } = new();
        }

        public class ReceiptItemViewModel
        {
            public string PaymentId { get; set; } = "";
            public string? InstalmentId { get; set; }
            public string StudentName { get; set; } = "-";
            public string ClassName { get; set; } = "-";
            public DateTime PaymentDate { get; set; }
            public decimal TotalPayment { get; set; }
            public string PaymentTypeDescription { get; set; } = "-";
            public string Terbilang { get; set; } = "-";
            public string KeteranganText { get; set; } = "CICILAN AKHIRUSANAH";
            public List<string> PaymentLines { get; set; } = new();
        }

        private static bool TryParseMoney(string raw, out decimal amount)
        {
            amount = 0m;
            if (string.IsNullOrWhiteSpace(raw)) return false;

            var cleaned = raw
                .Replace("Rp", "", StringComparison.OrdinalIgnoreCase)
                .Replace(" ", "");

            if (decimal.TryParse(cleaned, NumberStyles.Number, new CultureInfo("id-ID"), out amount))
                return true;

            if (decimal.TryParse(cleaned, NumberStyles.Number, CultureInfo.InvariantCulture, out amount))
                return true;

            var normalized = cleaned;
            if (normalized.Contains(",") && normalized.Contains("."))
            {
                normalized = normalized.LastIndexOf(',') > normalized.LastIndexOf('.')
                    ? normalized.Replace(".", "").Replace(",", ".")
                    : normalized.Replace(",", "");
            }
            else
            {
                normalized = normalized.Replace(".", "").Replace(",", ".");
            }

            return decimal.TryParse(normalized, NumberStyles.Number, CultureInfo.InvariantCulture, out amount);
        }

        private static DateTime CalculateDueDateForNextMonth(string itemCode, DateTime referenceDate)
        {
            var nextMonth = referenceDate.Date.AddMonths(1);
            int day = 10;

            if (!string.IsNullOrWhiteSpace(itemCode))
            {
                if (DateTime.TryParseExact(itemCode, new[] { "yyyy-MM-dd", "yyyy-M-d" }, CultureInfo.InvariantCulture, DateTimeStyles.None, out var parsedDate))
                {
                    day = parsedDate.Day;
                }
                else
                {
                    var match = Regex.Match(itemCode, @"(\d{1,2})\s*$");
                    if (match.Success && int.TryParse(match.Groups[1].Value, out var parsedDay))
                    {
                        day = parsedDay;
                    }
                }
            }

            day = Math.Max(1, Math.Min(day, DateTime.DaysInMonth(nextMonth.Year, nextMonth.Month)));
            return new DateTime(nextMonth.Year, nextMonth.Month, day);
        }
    }
}
