-- =============================================
-- mst_calendars
--    365 rows per academic year, one per date
-- =============================================
CREATE TABLE [dbo].[mst_calendars](
    [calendar_id]      [varchar](20)  NOT NULL,
    [academic_year_id] [varchar](20)  NOT NULL,
    [date]             [date]         NOT NULL,
    [day]              [varchar](10)  NOT NULL,
    [created_at]       [datetime]     NOT NULL,
    [updated_at]       [datetime]     NULL,
    [created_by]       [varchar](255) NOT NULL,
    [updated_by]       [varchar](255) NULL,
    PRIMARY KEY CLUSTERED ([calendar_id] ASC)
)
GO

ALTER TABLE [dbo].[mst_calendars] WITH CHECK
ADD FOREIGN KEY([academic_year_id]) REFERENCES [dbo].[mst_academic_years] ([academic_year_id])
GO

ALTER TABLE [dbo].[mst_events]
ADD [start_date]  [date] NULL,
    [end_date]    [date] NULL,
    [is_weekend]       [int]          NOT NULL DEFAULT 0,
    [is_holiday]       [int]          NOT NULL DEFAULT 0,
    [class_level]      [int]          NULL
GO

