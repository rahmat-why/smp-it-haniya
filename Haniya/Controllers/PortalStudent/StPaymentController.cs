using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Security.Claims;
using Haniya.Models;

namespace Haniya.Controllers.PortalStudent
{
    public class StPaymentController : Controller
    {
        private readonly IConfiguration _config;

        public StPaymentController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
            => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        // View
        public IActionResult Index()
        {
            return View("~/Views/PortalStudent/StPayment/Index.cshtml");
        }

        private (int draw, int start, int length, string searchValue, int orderColumnIndex, string orderDir) ParseDataTablesQuery()
        {
            var form = Request.HasFormContentType ? Request.Form : null;
            var q = Request.Query;

            string GetVal(string key)
            {
                if (form != null && form.ContainsKey(key)) return form[key].ToString();
                return q[key].ToString();
            }

            int.TryParse(GetVal("draw"), out var draw);
            if (draw <= 0) draw = 1;
            int.TryParse(GetVal("start"), out var start);
            if (start < 0) start = 0;
            int.TryParse(GetVal("length"), out var length);
            if (length <= 0) length = 10;

            var searchValue = GetVal("search[value]") ?? string.Empty;

            int.TryParse(GetVal("order[0][column]"), out var orderColumnIndex);
            var rawDir = (GetVal("order[0][dir]") ?? "").ToUpper();
            var orderDir = rawDir is "ASC" or "DESC" ? rawDir : "DESC";

            return (draw, start, length, searchValue, orderColumnIndex, orderDir);
        }

        private string GetPaymentOrderByColumn(int orderColumnIndex)
        {
            return orderColumnIndex switch
            {
                0 => "p.payment_date",
                1 => "c.class_name",
                2 => "COALESCE(pt.item_desc, p.payment_type)",
                3 => "p.total_price",
                4 => "p.total_payment",
                5 => "p.remaining_payment",
                6 => "p.status",
                7 => "COALESCE(pm.item_desc, p.payment_method)",
                _ => "p.payment_date"
            };
        }

        // Get payment list (by logged student)
        [HttpPost]
        public IActionResult GetMyPayments()
        {
            try
            {
                var (draw, start, length, search, orderColumnIndex, orderDir) = ParseDataTablesQuery();

                // Ambil StudentId dari login
                var studentId = User.FindFirst("StudentId")?.Value;

                if (string.IsNullOrEmpty(studentId))
                    return Json(DTOResponse.fail("Unauthorized", 401));


                using var conn = GetConn();
                conn.Open();

                var searchPattern = string.IsNullOrWhiteSpace(search) ? null : $"%{search.Trim()}%";
                var whereSql = @"
                WHERE s.student_id = @studentId
                  AND (
                        @search IS NULL
                        OR COALESCE(pt.item_desc, p.payment_type) LIKE @search
                        OR COALESCE(pm.item_desc, p.payment_method) LIKE @search
                        OR c.class_name LIKE @search
                        OR p.status LIKE @search
                  )";

                var orderBy = GetPaymentOrderByColumn(orderColumnIndex);

                var totalSql = @"
                SELECT COUNT(*)
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_students s ON sc.student_id = s.student_id
                LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                LEFT JOIN mst_classes c ON ac.class_id = c.class_id
                LEFT JOIN mst_detail_settings pt ON p.payment_type = pt.detail_id AND pt.header_id = 'PAYMENT_TYPE'
                LEFT JOIN mst_detail_settings pm ON p.payment_method = pm.detail_id AND pm.header_id = 'PAYMENT_METHOD'
                WHERE s.student_id = @studentId";

                var filteredSql = @"
                SELECT COUNT(*)
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_students s ON sc.student_id = s.student_id
                LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                LEFT JOIN mst_classes c ON ac.class_id = c.class_id
                LEFT JOIN mst_detail_settings pt ON p.payment_type = pt.detail_id AND pt.header_id = 'PAYMENT_TYPE'
                LEFT JOIN mst_detail_settings pm ON p.payment_method = pm.detail_id AND pm.header_id = 'PAYMENT_METHOD'
                " + whereSql;

                int recordsTotal;
                using (var totalCmd = new SqlCommand(totalSql, conn))
                {
                    totalCmd.Parameters.AddWithValue("@studentId", studentId);
                    recordsTotal = Convert.ToInt32(totalCmd.ExecuteScalar() ?? 0);
                }

                int recordsFiltered;
                using (var filteredCmd = new SqlCommand(filteredSql, conn))
                {
                    filteredCmd.Parameters.AddWithValue("@studentId", studentId);
                    filteredCmd.Parameters.AddWithValue("@search", (object)searchPattern ?? DBNull.Value);
                    recordsFiltered = Convert.ToInt32(filteredCmd.ExecuteScalar() ?? 0);
                }

                var sql = @"
                SELECT
                    p.payment_id,
                    p.payment_type,
                    p.total_price,
                    p.total_payment,
                    p.remaining_payment,
                    p.status,
                    p.payment_date,
                    p.payment_method,

                    COALESCE(pt.item_desc, p.payment_type) AS payment_type_desc,
                    COALESCE(pm.item_desc, p.payment_method) AS payment_method_desc,

                    c.class_name

                FROM txn_payments p

                JOIN mst_student_classes sc 
                    ON p.student_class_id = sc.student_class_id

                JOIN mst_students s
                    ON sc.student_id = s.student_id

                LEFT JOIN mst_academic_classes ac
                    ON sc.academic_class_id = ac.academic_class_id

                LEFT JOIN mst_classes c
                    ON ac.class_id = c.class_id

                LEFT JOIN mst_detail_settings pt 
                    ON p.payment_type = pt.detail_id 
                   AND pt.header_id = 'PAYMENT_TYPE'

                LEFT JOIN mst_detail_settings pm 
                    ON p.payment_method = pm.detail_id 
                   AND pm.header_id = 'PAYMENT_METHOD'

                " + whereSql + @"
                ORDER BY " + orderBy + " " + orderDir + @", p.payment_date DESC
                OFFSET @start ROWS FETCH NEXT @length ROWS ONLY
                ";


                var list = new List<object>();

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@studentId", studentId);
                    cmd.Parameters.AddWithValue("@search", (object)searchPattern ?? DBNull.Value);
                    cmd.Parameters.AddWithValue("@start", start);
                    cmd.Parameters.AddWithValue("@length", length);

                    using var r = cmd.ExecuteReader();

                    while (r.Read())
                    {
                        list.Add(new
                        {
                            payment_id = r["payment_id"]?.ToString(),

                            payment_type = r["payment_type_desc"]?.ToString(),

                            total_price = r["total_price"] as decimal?,
                            total_payment = r["total_payment"] as decimal?,
                            remaining_payment = r["remaining_payment"] as decimal?,

                            status = r["status"]?.ToString(),

                            payment_date = r["payment_date"] == DBNull.Value
                                ? null
                                : ((DateTime)r["payment_date"]).ToString("yyyy-MM-dd"),

                            payment_method = r["payment_method_desc"]?.ToString(),

                            class_name = r["class_name"]?.ToString()
                        });
                    }
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
    }
}
