using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Text.Json;
using Haniya.Models;
using System.Linq;

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

        public IActionResult GetAll(string academic_class_id = null, string status = null)
        {
            var (draw, start, length, _, _, _) = ParseDataTablesQuery();

            using var conn = GetConn();
            conn.Open();

            var totalSql = @"
        SELECT COUNT(*) 
        FROM txn_payments p
        LEFT JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
        WHERE (@classId IS NULL OR sc.academic_class_id = @classId)
          AND (@status IS NULL OR p.status = @status)";

            int recordsTotal;
            using (var cmd = new SqlCommand(totalSql, conn))
            {
                cmd.Parameters.AddWithValue("@classId", (object)academic_class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@status", (object)status ?? DBNull.Value);
                recordsTotal = (int)cmd.ExecuteScalar();
            }

            var sql = @"
        SELECT 
            p.payment_id,
            p.student_class_id,
            p.payment_type,
            p.total_price,
            p.total_payment,
            p.remaining_payment,
            p.status,
            p.payment_date,
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
        LEFT JOIN mst_detail_settings pm ON p.payment_method = pm.detail_id AND pm.header_id = 'PAYMENT_METHOD'
        WHERE (@classId IS NULL OR sc.academic_class_id = @classId)
          AND (@status IS NULL OR p.status = @status)
        GROUP BY 
            p.payment_id, p.student_class_id, p.payment_type, p.payment_method,
            p.total_price, p.total_payment, p.remaining_payment,
            p.status, p.payment_date, s.student_id, s.full_name, 
            s.first_name, s.last_name, s.nis, s.profile_photo, c.class_name,
            pt.item_desc, pm.item_desc
        ORDER BY p.payment_date DESC
        OFFSET @start ROWS FETCH NEXT @length ROWS ONLY";

            var list = new List<object>();
            using (var cmd = new SqlCommand(sql, conn))
            {
                cmd.Parameters.AddWithValue("@classId", (object)academic_class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@status", (object)status ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);

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
                        payment_date = r["payment_date"] == DBNull.Value ? null : ((DateTime)r["payment_date"]).ToString("yyyy-MM-dd"),
                        payment_method = r["payment_method_desc"]?.ToString(),
                        student_id = r["student_id"]?.ToString(),
                        student_name = r["student_name"]?.ToString(),
                        nis = r["nis"]?.ToString(),
                        profile_photo = r["profile_photo"]?.ToString() ?? "/image/no-image.png",
                        class_name = r["class_name"]?.ToString()
                    });
                }
            }

            return Json(new { draw, recordsTotal, recordsFiltered = recordsTotal, data = list });
        }

        private (int draw, int start, int length, string searchValue, int orderColumnIndex, string orderDir) ParseDataTablesQuery()
        {
            var q = Request.Query;
            int.TryParse(q["draw"], out var draw); draw = draw > 0 ? draw : 1;
            int.TryParse(q["start"], out var start);
            int.TryParse(q["length"], out var length); length = length > 0 ? length : 10;
            var searchValue = q["search[value]"].ToString() ?? "";
            int.TryParse(q["order[0][column]"], out var orderColumnIndex);
            var orderDir = q["order[0][dir]"].ToString().ToUpper() is "ASC" or "DESC" ? q["order[0][dir]"].ToString().ToUpper() : "ASC";
            return (draw, start, length, searchValue, orderColumnIndex, orderDir);
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
                p.payment_date,
                p.payment_method,
                COALESCE(s.full_name, CONCAT(s.first_name, ' ', s.last_name)) AS student_name,
                s.nis,
                COALESCE(pt.item_desc, p.payment_type) AS payment_type_desc,
                COALESCE(pm.item_desc, p.payment_method) AS payment_method_desc
            FROM txn_payments p
            LEFT JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
            LEFT JOIN mst_students s ON sc.student_id = s.student_id
            LEFT JOIN mst_detail_settings pt ON p.payment_type = pt.detail_id AND pt.header_id = 'PAYMENT_TYPE'
            LEFT JOIN mst_detail_settings pm ON p.payment_method = pm.detail_id AND pm.header_id = 'PAYMENT_METHOD'
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
                            payment_date = r["payment_date"] == DBNull.Value ? null : ((DateTime)r["payment_date"]).ToString("yyyy-MM-dd"),
                            payment_method = r["payment_method"]?.ToString(),
                            // Data tambahan untuk dropdown Edit
                            student_name = r["student_name"]?.ToString(),
                            nis = r["nis"]?.ToString(),
                            payment_type_desc = r["payment_type_desc"]?.ToString(),
                            payment_method_desc = r["payment_method_desc"]?.ToString()
                        };
                    }
                }

                if (header == null) return Json(DTOResponse.fail("Not found", 404));

                var instalments = new List<object>();
                var instSql = @"
            SELECT instalment_id, instalment_number, total_payment, 
                   payment_date, notes
            FROM txn_payment_instalments
            WHERE payment_id = @id
            ORDER BY instalment_number";

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
                            notes = r["notes"]?.ToString()
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
                    header.payment_date,
                    header.payment_method,
                    header.student_name,
                    header.nis,
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
                var student_class_id = f["student_class_id"].ToString();
                var payment_type = f["payment_type"].ToString();
                var total_price_str = f["total_price"].ToString();
                var payment_method = f["payment_method"].ToString();
                var payment_date_str = f["payment_date"].ToString();
                var rawInstalments = f["instalments"].ToString();

                if (string.IsNullOrWhiteSpace(student_class_id)) return Json(DTOResponse.fail("Student class is required", 400));
                if (string.IsNullOrWhiteSpace(payment_type)) return Json(DTOResponse.fail("Payment type is required", 400));
                if (string.IsNullOrWhiteSpace(total_price_str) || !decimal.TryParse(total_price_str, out var totalPrice) || totalPrice <= 0)
                    return Json(DTOResponse.fail("Valid total price is required", 400));

                DateTime paymentDate = DateTime.Now.Date;
                if (!string.IsNullOrWhiteSpace(payment_date_str) && DateTime.TryParse(payment_date_str, out var pd))
                    paymentDate = pd.Date;

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
                            notes = elem.TryGetProperty("notes", out var n) ? n.GetString() : ""
                        });
                    }
                }

                var validInsts = new List<(decimal Amount, DateTime Date, string Notes)>();
                foreach (var item in instalments)
                {
                    var amtStr = (string)item.total_payment;
                    var dateStr = (string)item.payment_date;
                    var note = (string)item.notes ?? "";

                    if (string.IsNullOrWhiteSpace(amtStr) && string.IsNullOrWhiteSpace(dateStr)) continue;

                    if (!decimal.TryParse(amtStr, out decimal amt) || amt <= 0)
                        return Json(DTOResponse.fail("Invalid instalment amount", 400));

                    if (!DateTime.TryParse(dateStr, out DateTime idate))
                        return Json(DTOResponse.fail("Invalid instalment date", 400));

                    validInsts.Add((amt, idate.Date, note));
                }

                decimal paid = validInsts.Sum(x => x.Amount);
                decimal remaining = totalPrice - paid;
                string status = paid <= 0 ? "UNPAID" : remaining > 0 ? "PARTIAL" : "PAID";

                using var conn = GetConn();
                conn.Open();

                var seqCmd = new SqlCommand("SELECT ISNULL(MAX(payment_id),'PAY0000') FROM txn_payments", conn);
                var seq = int.Parse(seqCmd.ExecuteScalar().ToString().Substring(3)) + 1;
                var paymentId = "PAY" + seq.ToString("D4");

                var headerSql = @"
                    INSERT INTO txn_payments (
                        payment_id, student_class_id, payment_type, total_price,
                        total_payment, remaining_payment, status,
                        payment_date, payment_method, created_at, created_by
                    ) VALUES (
                        @id, @scid, @ptype, @tprice, @tpaid, @rem, @stat,
                        @pdate, @pmeth, GETDATE(), @by
                    )";

                using (var cmd = new SqlCommand(headerSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", paymentId);
                    cmd.Parameters.AddWithValue("@scid", student_class_id);
                    cmd.Parameters.AddWithValue("@ptype", payment_type);
                    cmd.Parameters.AddWithValue("@tprice", totalPrice);
                    cmd.Parameters.AddWithValue("@tpaid", paid);
                    cmd.Parameters.AddWithValue("@rem", remaining);
                    cmd.Parameters.AddWithValue("@stat", status);
                    cmd.Parameters.AddWithValue("@pdate", paymentDate);
                    cmd.Parameters.AddWithValue("@pmeth", (object)payment_method ?? DBNull.Value);
                    cmd.Parameters.AddWithValue("@by", DBNull.Value);
                    cmd.ExecuteNonQuery();
                }

                if (validInsts.Count > 0)
                {
                    var instSeqCmd = new SqlCommand("SELECT ISNULL(MAX(instalment_id),'INS0000') FROM txn_payment_instalments", conn);
                    var instSeq = int.Parse(instSeqCmd.ExecuteScalar().ToString().Substring(3));

                    int number = 1;
                    foreach (var inst in validInsts)
                    {
                        instSeq++;
                        var instId = "INS" + instSeq.ToString("D4");

                        var detSql = @"
                            INSERT INTO txn_payment_instalments (
                                instalment_id, payment_id, instalment_number,
                                total_payment, payment_date, notes, created_at, created_by
                            ) VALUES (
                                @iid, @pid, @num, @amt, @date, @note, GETDATE(), @by
                            )";

                        using var dcmd = new SqlCommand(detSql, conn);
                        dcmd.Parameters.AddWithValue("@iid", instId);
                        dcmd.Parameters.AddWithValue("@pid", paymentId);
                        dcmd.Parameters.AddWithValue("@num", number++);
                        dcmd.Parameters.AddWithValue("@amt", inst.Amount);
                        dcmd.Parameters.AddWithValue("@date", inst.Date);
                        dcmd.Parameters.AddWithValue("@note", (object)inst.Notes ?? DBNull.Value);
                        dcmd.Parameters.AddWithValue("@by", DBNull.Value);
                        dcmd.ExecuteNonQuery();
                    }
                }

                return Json(DTOResponse.ok(null, "Payment created"));
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
                var student_class_id = f["student_class_id"].ToString();
                var payment_type = f["payment_type"].ToString();
                var total_price_str = f["total_price"].ToString();
                var payment_method = f["payment_method"].ToString();
                var payment_date_str = f["payment_date"].ToString();
                var rawInstalments = f["instalments"].ToString();

                if (string.IsNullOrWhiteSpace(payment_id)) return Json(DTOResponse.fail("Invalid payment ID", 400));
                if (string.IsNullOrWhiteSpace(student_class_id)) return Json(DTOResponse.fail("Student class is required", 400));
                if (string.IsNullOrWhiteSpace(payment_type)) return Json(DTOResponse.fail("Payment type is required", 400));
                if (string.IsNullOrWhiteSpace(total_price_str) || !decimal.TryParse(total_price_str, out var totalPrice) || totalPrice <= 0)
                    return Json(DTOResponse.fail("Valid total price is required", 400));

                DateTime paymentDate = DateTime.Now.Date;
                if (!string.IsNullOrWhiteSpace(payment_date_str) && DateTime.TryParse(payment_date_str, out var pd))
                    paymentDate = pd.Date;

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
                            notes = elem.TryGetProperty("notes", out var n) ? n.GetString() : ""
                        });
                    }
                }

                var validInsts = new List<(decimal Amount, DateTime Date, string Notes)>();
                foreach (var item in instalments)
                {
                    var amtStr = (string)item.total_payment;
                    var dateStr = (string)item.payment_date;
                    var note = (string)item.notes ?? "";

                    if (string.IsNullOrWhiteSpace(amtStr) && string.IsNullOrWhiteSpace(dateStr)) continue;

                    if (!decimal.TryParse(amtStr, out decimal amt) || amt <= 0)
                        return Json(DTOResponse.fail("Invalid instalment amount", 400));

                    if (!DateTime.TryParse(dateStr, out DateTime idate))
                        return Json(DTOResponse.fail("Invalid instalment date", 400));

                    validInsts.Add((amt, idate.Date, note));
                }

                decimal paid = validInsts.Sum(x => x.Amount);
                decimal remaining = totalPrice - paid;
                string status = paid <= 0 ? "UNPAID" : remaining > 0 ? "PARTIAL" : "PAID";

                using var conn = GetConn();
                conn.Open();

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

                using (var cmd = new SqlCommand(headerSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", payment_id);
                    cmd.Parameters.AddWithValue("@scid", student_class_id);
                    cmd.Parameters.AddWithValue("@ptype", payment_type);
                    cmd.Parameters.AddWithValue("@tprice", totalPrice);
                    cmd.Parameters.AddWithValue("@tpaid", paid);
                    cmd.Parameters.AddWithValue("@rem", remaining);
                    cmd.Parameters.AddWithValue("@stat", status);
                    cmd.Parameters.AddWithValue("@pdate", paymentDate);
                    cmd.Parameters.AddWithValue("@pmeth", (object)payment_method ?? DBNull.Value);
                    cmd.ExecuteNonQuery();
                }

                new SqlCommand("DELETE FROM txn_payment_instalments WHERE payment_id = @id", conn)
                { Parameters = { new SqlParameter("@id", payment_id) } }.ExecuteNonQuery();

                if (validInsts.Count > 0)
                {
                    var instSeqCmd = new SqlCommand("SELECT ISNULL(MAX(instalment_id),'INS0000') FROM txn_payment_instalments", conn);
                    var instSeq = int.Parse(instSeqCmd.ExecuteScalar().ToString().Substring(3));

                    int number = 1;
                    foreach (var inst in validInsts)
                    {
                        instSeq++;
                        var instId = "INS" + instSeq.ToString("D4");

                        var detSql = @"
                            INSERT INTO txn_payment_instalments (
                                instalment_id, payment_id, instalment_number,
                                total_payment, payment_date, notes, created_at, created_by
                            ) VALUES (
                                @iid, @pid, @num, @amt, @date, @note, GETDATE(), @by
                            )";

                        using var dcmd = new SqlCommand(detSql, conn);
                        dcmd.Parameters.AddWithValue("@iid", instId);
                        dcmd.Parameters.AddWithValue("@pid", payment_id);
                        dcmd.Parameters.AddWithValue("@num", number++);
                        dcmd.Parameters.AddWithValue("@amt", inst.Amount);
                        dcmd.Parameters.AddWithValue("@date", inst.Date);
                        dcmd.Parameters.AddWithValue("@note", (object)inst.Notes ?? DBNull.Value);
                        dcmd.Parameters.AddWithValue("@by", DBNull.Value);
                        dcmd.ExecuteNonQuery();
                    }
                }

                return Json(DTOResponse.ok(null, "Payment updated"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpPost]
        public IActionResult Delete([FromBody] DTORequest req)
        {
            try
            {
                if (string.IsNullOrEmpty(req?.id)) return Json(DTOResponse.fail("Invalid ID", 400));

                using var conn = GetConn();
                conn.Open();

                new SqlCommand("DELETE FROM txn_payment_instalments WHERE payment_id = @id", conn)
                { Parameters = { new SqlParameter("@id", req.id) } }.ExecuteNonQuery();

                new SqlCommand("DELETE FROM txn_payments WHERE payment_id = @id", conn)
                { Parameters = { new SqlParameter("@id", req.id) } }.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "Deleted"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpGet]
        public IActionResult GetTotalPriceByPaymentType(string paymentTypeId)
        {
            if (string.IsNullOrWhiteSpace(paymentTypeId))
            {
                return Json(new { success = false, message = "Payment type ID required", amount = 0m });
            }

            try
            {
                using var conn = GetConn();
                conn.Open();

                var sql = @"
            SELECT item_name
            FROM mst_detail_settings
            WHERE detail_id = @detailId
              AND header_id = 'PAYMENT_TYPE'
              AND status = 'ACTIVE'";

                string itemName = null;
                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@detailId", paymentTypeId);
                    itemName = cmd.ExecuteScalar()?.ToString();
                }

                if (string.IsNullOrWhiteSpace(itemName))
                {
                    return Json(new { success = false, message = "Price not found for this payment type", amount = 0m });
                }

                if (decimal.TryParse(itemName.Replace(",", "").Replace(".", ""), System.Globalization.NumberStyles.Any, System.Globalization.CultureInfo.InvariantCulture, out decimal amount))
                {
                    return Json(new { success = true, amount });
                }

                return Json(new { success = false, message = "Invalid price format in database", amount = 0m });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message, amount = 0m });
            }
        }
    }
}