alter table [dbo].[mst_rps] add [minimum_value] [decimal](5, 2) NULL;

ALTER table [dbo].[txn_grades] DROP COLUMN minimum_value;