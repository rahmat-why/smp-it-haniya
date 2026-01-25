using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Linq;
using System.Text.Json;
using Haniya.Models;

namespace Haniya.Controllers.PortalAdmin
{
    public class PaymentController : Controller
    {
        private readonly IConfiguration _config;

        public PaymentController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        private List<dynamic> GetStudentClassOptions()
        {
            var list = new List<dynamic>();
            using var conn = GetConn();
            conn.Open();

            // Adjust display name to your actual columns
            var sql = @"
                SELECT student_class_id
                FROM mst_student_classes
                ORDER BY student_class_id";

            using var cmd = new SqlCommand(sql, conn);
            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                var id = rd["student_class_id"]?.ToString();
                list.Add(new
                {
                    Id = id,
                    Name = id
                });
            }
            return list;
        }

        private class InstalmentDto
        {
            public string total_payment { get; set; } = "";
            public string payment_date { get; set; } = "";
            public string notes { get; set; } = "";
        }

        // ===== PAGES =====

        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/Payment/Index.cshtml");
        }

        public IActionResult Create()
        {
            ViewBag.StudentClassOptions = GetStudentClassOptions();
            return View("~/Views/PortalAdmin/Payment/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.paymentId = id;
            ViewBag.StudentClassOptions = GetStudentClassOptions();
            return View("~/Views/PortalAdmin/Payment/Edit.cshtml");
        }

        // ===== API =====

        [HttpGet]
        public IActionResult GetAll()
        {
            try
            {
                var list = new List<object>();
                using var conn = GetConn();
                conn.Open();

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
                        COUNT(i.instalment_id) AS instalment_count
                    FROM txn_payments p
                    LEFT JOIN txn_payment_instalments i
                        ON p.payment_id = i.payment_id
                    GROUP BY p.payment_id, p.student_class_id, p.payment_type, p.total_price,
                             p.total_payment, p.remaining_payment, p.status, p.payment_date
                    ORDER BY p.payment_date DESC, p.payment_id";

                using var cmd = new SqlCommand(sql, conn);
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    list.Add(new
                    {
                        payment_id = rd["payment_id"],
                        student_class_id = rd["student_class_id"],
                        payment_type = rd["payment_type"],
                        total_price = rd["total_price"],
                        total_payment = rd["total_payment"],
                        remaining_payment = rd["remaining_payment"],
                        status = rd["status"],
                        payment_date = rd["payment_date"],
                        instalment_count = rd["instalment_count"]
                    });
                }

                return Json(DTOResponse.ok(list));
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

                var sql = @"
                    SELECT *
                    FROM txn_payments
                    WHERE payment_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);

                using var rd = cmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("data not found", 404));

                var pmt = new
                {
                    payment_id = rd["payment_id"]?.ToString(),
                    student_class_id = rd["student_class_id"]?.ToString(),
                    payment_type = rd["payment_type"]?.ToString(),
                    total_payment = rd["total_payment"]?.ToString(),
                    remaining_payment = rd["remaining_payment"]?.ToString(),
                    status = rd["status"]?.ToString(),
                    notes = rd["notes"]?.ToString(),
                    total_price = rd["total_price"]?.ToString(),
                    payment_date = rd["payment_date"]?.ToString(),
                    payment_method = rd["payment_method"]?.ToString()
                };

                rd.Close();

                var insts = new List<object>();

                var dsql = @"
                    SELECT
                        instalment_id,
                        instalment_number,
                        total_payment,
                        payment_date,
                        notes
                    FROM txn_payment_instalments
                    WHERE payment_id = @id
                    ORDER BY instalment_number";

                using var dcmd = new SqlCommand(dsql, conn);
                dcmd.Parameters.AddWithValue("@id", id);

                using var drd = dcmd.ExecuteReader();
                while (drd.Read())
                {
                    insts.Add(new
                    {
                        instalment_id = drd["instalment_id"]?.ToString(),
                        instalment_number = drd["instalment_number"],
                        total_payment = drd["total_payment"]?.ToString(),
                        payment_date = drd["payment_date"]?.ToString(),
                        notes = drd["notes"]?.ToString()
                    });
                }

                return Json(DTOResponse.ok(new
                {
                    pmt.payment_id,
                    pmt.student_class_id,
                    pmt.payment_type,
                    pmt.total_payment,
                    pmt.remaining_payment,
                    pmt.status,
                    pmt.notes,
                    pmt.total_price,
                    pmt.payment_date,
                    pmt.payment_method,
                    instalments = insts
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

                var studentClassId = f["student_class_id"].ToString();
                var paymentType = f["payment_type"].ToString();
                var totalPriceStr = f["total_price"].ToString();
                var paymentMethod = f["payment_method"].ToString();
                var notes = f["notes"].ToString();
                var paymentDateStr = f["payment_date"].ToString();
                var rawInst = f["instalments"].ToString();

                if (string.IsNullOrWhiteSpace(studentClassId))
                    return Json(DTOResponse.fail("student class is required", 400));
                if (string.IsNullOrWhiteSpace(paymentType))
                    return Json(DTOResponse.fail("payment type is required", 400));
                if (string.IsNullOrWhiteSpace(totalPriceStr) || !decimal.TryParse(totalPriceStr, out var totalPrice))
                    return Json(DTOResponse.fail("valid total price is required", 400));

                DateTime paymentDate = DateTime.Today;
                if (!string.IsNullOrWhiteSpace(paymentDateStr) &&
                    DateTime.TryParse(paymentDateStr, out var pd))
                {
                    paymentDate = pd.Date;
                }

                List<InstalmentDto> instalments;
                try
                {
                    instalments = string.IsNullOrWhiteSpace(rawInst)
                        ? new List<InstalmentDto>()
                        : JsonSerializer.Deserialize<List<InstalmentDto>>(rawInst) ?? new List<InstalmentDto>();
                }
                catch
                {
                    return Json(DTOResponse.fail("invalid instalments format", 400));
                }

                var validInstalments = new List<(decimal total, DateTime date, string notes)>();
                foreach (var it in instalments)
                {
                    if (string.IsNullOrWhiteSpace(it.total_payment) &&
                        string.IsNullOrWhiteSpace(it.payment_date) &&
                        string.IsNullOrWhiteSpace(it.notes))
                    {
                        continue;
                    }

                    if (!decimal.TryParse(it.total_payment, out var ipay))
                        return Json(DTOResponse.fail("invalid instalment total_payment", 400));

                    if (!DateTime.TryParse(it.payment_date, out var idate))
                        return Json(DTOResponse.fail("invalid instalment payment_date", 400));

                    validInstalments.Add((ipay, idate.Date, it.notes ?? ""));
                }

                decimal sumPaid = validInstalments.Sum(x => x.total);
                decimal remaining = totalPrice - sumPaid;
                string status;
                if (sumPaid <= 0)
                    status = "UNPAID";
                else if (remaining > 0)
                    status = "PARTIAL";
                else
                    status = "PAID";

                using var conn = GetConn();
                conn.Open();

                // payment_id
                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(payment_id),'PAY0000') FROM txn_payments",
                    conn
                );
                var lastId = lastCmd.ExecuteScalar()?.ToString() ?? "PAY0000";
                var next = int.Parse(lastId.Substring(3)) + 1;
                var paymentId = "PAY" + next.ToString("D4");

                // insert header
                var sql = @"
                    INSERT INTO txn_payments (
                        payment_id,
                        student_class_id,
                        payment_type,
                        total_payment,
                        remaining_payment,
                        status,
                        notes,
                        created_at,
                        total_price,
                        payment_date,
                        payment_method
                    ) VALUES (
                        @id,
                        @sid,
                        @type,
                        @tp,
                        @rp,
                        @sts,
                        @nts,
                        GETDATE(),
                        @price,
                        @pdate,
                        @pmethod
                    )";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", paymentId);
                cmd.Parameters.AddWithValue("@sid", studentClassId);
                cmd.Parameters.AddWithValue("@type", paymentType);
                cmd.Parameters.AddWithValue("@tp", sumPaid);
                cmd.Parameters.AddWithValue("@rp", remaining);
                cmd.Parameters.AddWithValue("@sts", status);
                cmd.Parameters.AddWithValue("@nts", (object?)notes ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@price", totalPrice);
                cmd.Parameters.AddWithValue("@pdate", paymentDate);
                cmd.Parameters.AddWithValue("@pmethod", (object?)paymentMethod ?? DBNull.Value);
                cmd.ExecuteNonQuery();

                // instalment_id seed
                var lastInstCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(instalment_id),'INS0000') FROM txn_payment_instalments",
                    conn
                );
                var lastInstId = lastInstCmd.ExecuteScalar()?.ToString() ?? "INS0000";
                var currentInst = int.Parse(lastInstId.Substring(3));

                int number = 1;
                foreach (var it in validInstalments)
                {
                    currentInst++;
                    var instId = "INS" + currentInst.ToString("D4");

                    var dsql = @"
                        INSERT INTO txn_payment_instalments (
                            instalment_id,
                            payment_id,
                            instalment_number,
                            total_payment,
                            payment_date,
                            notes,
                            created_at
                        ) VALUES (
                            @iid,
                            @pid,
                            @num,
                            @tp,
                            @pdate,
                            @nts,
                            GETDATE()
                        )";

                    using var dcmd = new SqlCommand(dsql, conn);
                    dcmd.Parameters.AddWithValue("@iid", instId);
                    dcmd.Parameters.AddWithValue("@pid", paymentId);
                    dcmd.Parameters.AddWithValue("@num", number++);
                    dcmd.Parameters.AddWithValue("@tp", it.total);
                    dcmd.Parameters.AddWithValue("@pdate", it.date);
                    dcmd.Parameters.AddWithValue("@nts", (object?)it.notes ?? DBNull.Value);
                    dcmd.ExecuteNonQuery();
                }

                return Json(DTOResponse.ok(null, "payment created"));
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

                var paymentId = f["payment_id"].ToString();
                var studentClassId = f["student_class_id"].ToString();
                var paymentType = f["payment_type"].ToString();
                var totalPriceStr = f["total_price"].ToString();
                var paymentMethod = f["payment_method"].ToString();
                var notes = f["notes"].ToString();
                var paymentDateStr = f["payment_date"].ToString();
                var rawInst = f["instalments"].ToString();

                if (string.IsNullOrWhiteSpace(paymentId))
                    return Json(DTOResponse.fail("invalid payment id", 400));
                if (string.IsNullOrWhiteSpace(studentClassId))
                    return Json(DTOResponse.fail("student class is required", 400));
                if (string.IsNullOrWhiteSpace(paymentType))
                    return Json(DTOResponse.fail("payment type is required", 400));
                if (string.IsNullOrWhiteSpace(totalPriceStr) || !decimal.TryParse(totalPriceStr, out var totalPrice))
                    return Json(DTOResponse.fail("valid total price is required", 400));

                DateTime paymentDate = DateTime.Today;
                if (!string.IsNullOrWhiteSpace(paymentDateStr) &&
                    DateTime.TryParse(paymentDateStr, out var pd))
                {
                    paymentDate = pd.Date;
                }

                List<InstalmentDto> instalments;
                try
                {
                    instalments = string.IsNullOrWhiteSpace(rawInst)
                        ? new List<InstalmentDto>()
                        : JsonSerializer.Deserialize<List<InstalmentDto>>(rawInst) ?? new List<InstalmentDto>();
                }
                catch
                {
                    return Json(DTOResponse.fail("invalid instalments format", 400));
                }

                var validInstalments = new List<(decimal total, DateTime date, string notes)>();
                foreach (var it in instalments)
                {
                    if (string.IsNullOrWhiteSpace(it.total_payment) &&
                        string.IsNullOrWhiteSpace(it.payment_date) &&
                        string.IsNullOrWhiteSpace(it.notes))
                    {
                        continue;
                    }

                    if (!decimal.TryParse(it.total_payment, out var ipay))
                        return Json(DTOResponse.fail("invalid instalment total_payment", 400));

                    if (!DateTime.TryParse(it.payment_date, out var idate))
                        return Json(DTOResponse.fail("invalid instalment payment_date", 400));

                    validInstalments.Add((ipay, idate.Date, it.notes ?? ""));
                }

                decimal sumPaid = validInstalments.Sum(x => x.total);
                decimal remaining = totalPrice - sumPaid;
                string status;
                if (sumPaid <= 0)
                    status = "UNPAID";
                else if (remaining > 0)
                    status = "PARTIAL";
                else
                    status = "PAID";

                using var conn = GetConn();
                conn.Open();

                // update header
                var sql = @"
                    UPDATE txn_payments SET
                        student_class_id = @sid,
                        payment_type = @type,
                        total_payment = @tp,
                        remaining_payment = @rp,
                        status = @sts,
                        notes = @nts,
                        updated_at = GETDATE(),
                        total_price = @price,
                        payment_date = @pdate,
                        payment_method = @pmethod
                    WHERE payment_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", paymentId);
                cmd.Parameters.AddWithValue("@sid", studentClassId);
                cmd.Parameters.AddWithValue("@type", paymentType);
                cmd.Parameters.AddWithValue("@tp", sumPaid);
                cmd.Parameters.AddWithValue("@rp", remaining);
                cmd.Parameters.AddWithValue("@sts", status);
                cmd.Parameters.AddWithValue("@nts", (object?)notes ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@price", totalPrice);
                cmd.Parameters.AddWithValue("@pdate", paymentDate);
                cmd.Parameters.AddWithValue("@pmethod", (object?)paymentMethod ?? DBNull.Value);
                cmd.ExecuteNonQuery();

                // delete old instalments
                var delCmd = new SqlCommand(
                    "DELETE FROM txn_payment_instalments WHERE payment_id=@id",
                    conn
                );
                delCmd.Parameters.AddWithValue("@id", paymentId);
                delCmd.ExecuteNonQuery();

                // insert new instalments
                var lastInstCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(instalment_id),'INS0000') FROM txn_payment_instalments",
                    conn
                );
                var lastInstId = lastInstCmd.ExecuteScalar()?.ToString() ?? "INS0000";
                var currentInst = int.Parse(lastInstId.Substring(3));

                int number = 1;
                foreach (var it in validInstalments)
                {
                    currentInst++;
                    var instId = "INS" + currentInst.ToString("D4");

                    var dsql = @"
                        INSERT INTO txn_payment_instalments (
                            instalment_id,
                            payment_id,
                            instalment_number,
                            total_payment,
                            payment_date,
                            notes,
                            created_at
                        ) VALUES (
                            @iid,
                            @pid,
                            @num,
                            @tp,
                            @pdate,
                            @nts,
                            GETDATE()
                        )";

                    using var dcmd = new SqlCommand(dsql, conn);
                    dcmd.Parameters.AddWithValue("@iid", instId);
                    dcmd.Parameters.AddWithValue("@pid", paymentId);
                    dcmd.Parameters.AddWithValue("@num", number++);
                    dcmd.Parameters.AddWithValue("@tp", it.total);
                    dcmd.Parameters.AddWithValue("@pdate", it.date);
                    dcmd.Parameters.AddWithValue("@nts", (object?)it.notes ?? DBNull.Value);
                    dcmd.ExecuteNonQuery();
                }

                return Json(DTOResponse.ok(null, "payment updated"));
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
                    return Json(DTOResponse.fail("invalid payment id", 400));

                using var conn = GetConn();
                conn.Open();

                var delDet = new SqlCommand(
                    "DELETE FROM txn_payment_instalments WHERE payment_id=@id",
                    conn
                );
                delDet.Parameters.AddWithValue("@id", req.id);
                delDet.ExecuteNonQuery();

                var cmd = new SqlCommand(
                    "DELETE FROM txn_payments WHERE payment_id=@id",
                    conn
                );
                cmd.Parameters.AddWithValue("@id", req.id);
                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "payment deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}