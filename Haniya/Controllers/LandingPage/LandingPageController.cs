using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;

namespace Haniya.Controllers.LandingPage
{
    public class LandingPageController : Controller
    {
        private readonly IConfiguration _config;

        public LandingPageController(IConfiguration config)
        {
            _config = config;
        }

        [HttpGet]
        [Route("")]
        [Route("home")]
        public IActionResult HomeLP()
        {
            return View();
        }

        [HttpGet]
        public JsonResult GetHomeData()
        {
            var data = new Dictionary<string, string>();

            string connString = _config.GetConnectionString("DefaultConnection");

            using (SqlConnection conn = new SqlConnection(connString))
            {
                conn.Open();

                string query = @"
                    SELECT detail_id, item_name, item_desc
                    FROM mst_detail_setting_landingpages
                    WHERE header_id = 'HOME'
                    AND status = 'ACTIVE'
                ";

                using (SqlCommand cmd = new SqlCommand(query, conn))
                using (SqlDataReader reader = cmd.ExecuteReader())
                {
                    while (reader.Read())
                    {
                        string key = reader["detail_id"].ToString();
                        data[key + "_NAME"] = reader["item_name"]?.ToString();
                        data[key + "_DESC"] = reader["item_desc"]?.ToString();
                    }
                }

                string principalQuery = @"
                    SELECT TOP 1
                        LTRIM(RTRIM(ISNULL(item_code, ''))) AS item_code,
                        ISNULL(item_name, '') AS item_name,
                        ISNULL(item_desc, '') AS item_desc
                    FROM mst_detail_settings
                    WHERE header_id = 'PRINCIPAL'
                      AND detail_id = 'PRINCIPAL_NAME'
                      AND status = 'ACTIVE'
                ";

                using (SqlCommand cmd = new SqlCommand(principalQuery, conn))
                using (SqlDataReader reader = cmd.ExecuteReader())
                {
                    if (reader.Read())
                    {
                        data["PRINCIPAL_IMAGE"] = reader["item_code"]?.ToString();
                        data["PRINCIPAL_NAME"] = reader["item_name"]?.ToString();
                        data["PRINCIPAL_DESC"] = reader["item_desc"]?.ToString();
                    }
                }
            }

            return Json(data);
        }


        [HttpGet]
        [Route("about")]
        public IActionResult AboutUsLP()
        {
            return View();
        }

        [HttpGet]
        public JsonResult GetAboutData()
        {
            var data = new Dictionary<string, string>();

            string connString = _config.GetConnectionString("DefaultConnection");

            using (SqlConnection conn = new SqlConnection(connString))
            {
                conn.Open();

                string query = @"
                    SELECT detail_id, item_name, item_desc
                    FROM mst_detail_setting_landingpages
                    WHERE header_id = 'ABOUT'
                    AND status = 'ACTIVE'
                ";

                using (SqlCommand cmd = new SqlCommand(query, conn))
                using (SqlDataReader reader = cmd.ExecuteReader())
                {
                    while (reader.Read())
                    {
                        string key = reader["detail_id"].ToString();
                        data[key + "_NAME"] = reader["item_name"]?.ToString();
                        data[key + "_DESC"] = reader["item_desc"]?.ToString();
                    }
                }
            }

            return Json(data);
        }

        [HttpGet]
        [Route("academic")]
        public IActionResult AcademicLP()
        {
            return View();
        }

        [HttpGet]
        public JsonResult GetAcademicData()
        {
            var data = new Dictionary<string, string>();

            string connString = _config.GetConnectionString("DefaultConnection");

            using (SqlConnection conn = new SqlConnection(connString))
            {
                conn.Open();

                string query = @"
                    SELECT detail_id, item_name, item_desc
                    FROM mst_detail_setting_landingpages
                    WHERE header_id = 'ACADEMIC'
                    AND detail_id NOT LIKE 'ACADEMIC_CURRIC_SD%'
                    AND status = 'ACTIVE'
                ";

                using (SqlCommand cmd = new SqlCommand(query, conn))
                using (SqlDataReader reader = cmd.ExecuteReader())
                {
                    while (reader.Read())
                    {
                        string key = reader["detail_id"].ToString();
                        data[key + "_NAME"] = reader["item_name"]?.ToString();
                        data[key + "_DESC"] = reader["item_desc"]?.ToString();
                    }
                }
            }

            return Json(data);
        }
        [HttpGet]
        [Route("contact")]
        public IActionResult ContactLP()
        {
            return View();
        }

        [HttpGet]
        public JsonResult GetContactData()
        {
            var data = new Dictionary<string, string>();

            string connString = _config.GetConnectionString("DefaultConnection");

            using (SqlConnection conn = new SqlConnection(connString))
            {
                conn.Open();

                string query = @"
                    SELECT detail_id, item_name, item_desc
                    FROM mst_detail_setting_landingpages
                    WHERE header_id = 'CONTACT'
                    AND status = 'ACTIVE'
                ";

                using (SqlCommand cmd = new SqlCommand(query, conn))
                using (SqlDataReader reader = cmd.ExecuteReader())
                {
                    while (reader.Read())
                    {
                        string key = reader["detail_id"].ToString();
                        data[key + "_NAME"] = reader["item_name"]?.ToString();
                        data[key + "_DESC"] = reader["item_desc"]?.ToString();
                    }
                }
            }

            return Json(data);
        }

        [HttpGet]
        [Route("news-event")]
        public IActionResult EventLP()
        {
            return View();
        }

        [HttpGet]
        [Route("news-event/{id}")]
        public IActionResult EventDetailLP(string id)
        {
            ViewBag.EventId = id;
            return View();
        }

        [HttpGet]
        public JsonResult GetEventData(int page = 1)
        {
            int pageSize = 6;
            int offset = (page - 1) * pageSize;

            var events = new List<object>();

            string connString = _config.GetConnectionString("DefaultConnection");

            using (SqlConnection conn = new SqlConnection(connString))
            {
                conn.Open();

                string query = @"
                    SELECT event_id, event_name, profile_photo, created_at
                    FROM mst_events
                    ORDER BY created_at DESC
                    OFFSET @offset ROWS
                    FETCH NEXT @pageSize ROWS ONLY
                ";

                using (SqlCommand cmd = new SqlCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@offset", offset);
                    cmd.Parameters.AddWithValue("@pageSize", pageSize);

                    using (SqlDataReader reader = cmd.ExecuteReader())
                    {
                        while (reader.Read())
                        {
                            events.Add(new
                            {
                                id = reader["event_id"].ToString(),
                                name = reader["event_name"].ToString(),
                                image = reader["profile_photo"].ToString(),
                                date = Convert.ToDateTime(reader["created_at"]).ToString("yyyy-MM-dd")
                            });
                        }
                    }
                }
            }

            return Json(events);
        }

        public JsonResult GetEventDetail(string id)
        {
            object data = null;

            string connString = _config.GetConnectionString("DefaultConnection");

            using (SqlConnection conn = new SqlConnection(connString))
            {
                conn.Open();

                string query = @"
                    SELECT event_id, event_name, description, location, status,
                           profile_photo, created_at
                    FROM mst_events
                    WHERE event_id = @Id
                ";

                using (SqlCommand cmd = new SqlCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);

                    using (SqlDataReader reader = cmd.ExecuteReader())
                    {
                        if (reader.Read())
                        {
                            data = new
                            {
                                id = reader["event_id"].ToString(),
                                name = reader["event_name"].ToString(),
                                description = reader["description"].ToString(),
                                location = reader["location"].ToString(),
                                status = reader["status"].ToString(),
                                image = reader["profile_photo"].ToString(),
                                date = Convert.ToDateTime(reader["created_at"]).ToString("dd MMM yyyy")
                            };
                        }
                    }
                }
            }

            return Json(data);
        }



        [HttpGet]
        [Route("news-article")]
        public IActionResult ArticleLP()
        {
            return View();
        }

        [HttpGet]
        [Route("news-article/{id}")]
        public IActionResult ArticleDetailLP(string id)
        {
            ViewBag.ArticleId = id;
            return View();
        }

        [HttpGet]
        public JsonResult GetArticleData(int page = 1)
        {
            int pageSize = 6;
            int offset = (page - 1) * pageSize;

            var events = new List<object>();

            string connString = _config.GetConnectionString("DefaultConnection");

            using (SqlConnection conn = new SqlConnection(connString))
            {
                conn.Open();

                string query = @"
                    SELECT article_id, title, image, created_at
                    FROM mst_articles
                    WHERE UPPER(ISNULL(status, '')) = 'PUBLISHED'
                    ORDER BY created_at DESC
                    OFFSET @offset ROWS
                    FETCH NEXT @pageSize ROWS ONLY
                ";

                using (SqlCommand cmd = new SqlCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@offset", offset);
                    cmd.Parameters.AddWithValue("@pageSize", pageSize);

                    using (SqlDataReader reader = cmd.ExecuteReader())
                    {
                        while (reader.Read())
                        {
                            events.Add(new
                            {
                                id = reader["article_id"].ToString(),
                                name = reader["title"].ToString(),
                                image = reader["image"].ToString(),
                                date = Convert.ToDateTime(reader["created_at"]).ToString("yyyy-MM-dd")
                            });
                        }
                    }
                }
            }

            return Json(events);
        }

        public JsonResult GetArticleDetail(string id)
        {
            object data = null;

            string connString = _config.GetConnectionString("DefaultConnection");

            using (SqlConnection conn = new SqlConnection(connString))
            {
                conn.Open();

                string query = @"
                    SELECT article_id, title, content, status,
                           image, created_at
                    FROM mst_articles
                    WHERE article_id = @Id
                ";

                using (SqlCommand cmd = new SqlCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);

                    using (SqlDataReader reader = cmd.ExecuteReader())
                    {
                        if (reader.Read())
                        {
                            data = new
                            {
                                id = reader["article_id"].ToString(),
                                name = reader["title"].ToString(),
                                description = reader["content"].ToString(),
                                status = reader["status"].ToString(),
                                image = reader["image"].ToString(),
                                date = Convert.ToDateTime(reader["created_at"]).ToString("dd MMM yyyy")
                            };
                        }
                    }
                }
            }

            return Json(data);
        }
    }
}

