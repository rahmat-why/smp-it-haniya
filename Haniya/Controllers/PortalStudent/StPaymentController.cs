using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using Haniya.Models;
using Microsoft.AspNetCore.Authorization;

namespace Haniya.Controllers.PortalStudent
{
    [Authorize]
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

        public class ListSort
        {
            public string field { get; set; } = "date";
            public string order { get; set; } = "desc";
        }

        public class ListRequest
        {
            public int page { get; set; } = 1;
            public int limit { get; set; } = 10;
            public Dictionary<string, string>? filters { get; set; }
            public ListSort? sort { get; set; }
        }

        // Get payment list (by logged student)
        [HttpPost]
        public IActionResult GetMyPayments([FromBody] ListRequest? req)
        {
            try
            {
                req ??= new ListRequest();
                var page = req.page <= 0 ? 1 : req.page;
                var limit = req.limit <= 0 ? 10 : Math.Min(req.limit, 50);

                var filters = req.filters ?? new Dictionary<string, string>();
                filters.TryGetValue("search", out var search);
                search = string.IsNullOrWhiteSpace(search) ? null : search.Trim();

                // Ambil StudentId dari login
                var studentId = User.FindFirst("StudentId")?.Value;

                if (string.IsNullOrEmpty(studentId))
                    return Json(DTOResponse.fail("Unauthorized", 401));


                using var conn = GetConn();
                conn.Open();

                var searchPattern = search == null ? null : $"%{search}%";

                var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
                {
                    ["date"] = "COALESCE(p.due_date, p.payment_date)",
                    ["class"] = "c.class_name",
                    ["type"] = "COALESCE(pt.item_desc, p.payment_type)",
                    ["total"] = "p.total_price",
                    ["paid"] = "p.total_payment",
                    ["remaining"] = "p.remaining_payment",
                    ["status"] = "p.status",
                    ["method"] = "COALESCE(pm.item_desc, p.payment_method)"
                };
                var sort = req.sort ?? new ListSort();
                var orderBy = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "COALESCE(p.due_date, p.payment_date)";
                var orderDir = string.Equals(sort.order, "asc", StringComparison.OrdinalIgnoreCase) ? "ASC" : "DESC";
                var secondaryOrder = string.Equals(orderBy, "COALESCE(p.due_date, p.payment_date)", StringComparison.OrdinalIgnoreCase)
                    ? "p.payment_id DESC"
                    : "COALESCE(p.due_date, p.payment_date) DESC";

                var whereSql = @"
                WHERE s.student_id = @studentId
                  AND (
                        @search IS NULL
                        OR COALESCE(pt.item_desc, p.payment_type) LIKE @search
                        OR COALESCE(pm.item_desc, p.payment_method) LIKE @search
                        OR c.class_name LIKE @search
                        OR p.status LIKE @search
                  )";

                var totalSql = @"
                SELECT COUNT(*)
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_students s ON sc.student_id = s.student_id
                LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                LEFT JOIN mst_classes c ON ac.class_id = c.class_id
                LEFT JOIN mst_detail_settings pt ON p.payment_type = pt.detail_id AND pt.header_id = 'PAYMENT_TYPE'
                LEFT JOIN mst_detail_settings pm ON pm.header_id = 'PAYMENT_METHOD'
                                                AND (p.payment_method = pm.detail_id OR p.payment_method = pm.item_code)
                WHERE s.student_id = @studentId";

                var filteredSql = @"
                SELECT COUNT(*)
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_students s ON sc.student_id = s.student_id
                LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                LEFT JOIN mst_classes c ON ac.class_id = c.class_id
                LEFT JOIN mst_detail_settings pt ON p.payment_type = pt.detail_id AND pt.header_id = 'PAYMENT_TYPE'
                LEFT JOIN mst_detail_settings pm ON pm.header_id = 'PAYMENT_METHOD'
                                                AND (p.payment_method = pm.detail_id OR p.payment_method = pm.item_code)
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

                var totalPages = recordsFiltered == 0 ? 1 : (int)Math.Ceiling(recordsFiltered / (double)limit);
                page = Math.Min(page, totalPages);
                var offset = (page - 1) * limit;

                var sql = @"
                SELECT
                    p.payment_id,
                    p.payment_type,
                    p.total_price,
                    p.total_payment,
                    p.remaining_payment,
                    p.status,
                    p.payment_date,
                    p.due_date,
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
                    ON pm.header_id = 'PAYMENT_METHOD'
                   AND (p.payment_method = pm.detail_id OR p.payment_method = pm.item_code)

                " + whereSql + @"
                ORDER BY " + orderBy + " " + orderDir + @", " + secondaryOrder + @"
                OFFSET @start ROWS FETCH NEXT @length ROWS ONLY
                ";


                var list = new List<object>();

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@studentId", studentId);
                    cmd.Parameters.AddWithValue("@search", (object)searchPattern ?? DBNull.Value);
                    cmd.Parameters.AddWithValue("@start", offset);
                    cmd.Parameters.AddWithValue("@length", limit);

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

                            due_date = r["due_date"] == DBNull.Value
                                ? null
                                : ((DateTime)r["due_date"]).ToString("yyyy-MM-dd"),

                            payment_method = r["payment_method_desc"]?.ToString(),

                            class_name = r["class_name"]?.ToString()
                        });
                    }
                }

                var hasNextPage = (offset + list.Count) < recordsFiltered;
                return Json(DTOResponse.ok(new
                {
                    data = list,
                    hasNextPage,
                    totalRows = recordsFiltered,
                    currentPage = page,
                    limit,
                    totalAll = recordsTotal
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
