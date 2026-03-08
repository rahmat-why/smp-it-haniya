INSERT INTO [SMP_IT_HANIYA].[dbo].[mst_detail_setting_landingpages]
(
	[detail_id],
	[header_id],
    [item_code],
    [item_name],
    [item_desc],
    [status],
    [item_type],
    [created_at],
	[updated_at],
    [created_by],
    [updated_by]
)
VALUES
(
    'ABOUT_HEADSC_NAME1',
    'ABOUT',
	'HEADSCHOOL',
    'HEADSCHOOL NAME',
	'Cholil Saputra, S.Pd.I',
    'ACTIVE',
    'text',
    GETDATE(),
	GETDATE(),
    'admin',
    'admin'
);

INSERT INTO [SMP_IT_HANIYA].[dbo].[mst_detail_setting_landingpages]
(
	[detail_id],
	[header_id],
    [item_code],
    [item_name],
    [item_desc],
    [status],
    [item_type],
    [created_at],
	[updated_at],
    [created_by],
    [updated_by]
)
VALUES
(
    'ABOUT_HEADSC_NPK1',
    'ABOUT',
	'HEADSCHOOL',
    'HEADSCHOOL NPK',
	'NPK0000',
    'ACTIVE',
    'text',
    GETDATE(),
	GETDATE(),
    'admin',
    'admin'
);

INSERT INTO [SMP_IT_HANIYA].[dbo].[mst_header_settings]
(
	[header_id],
    [title],
    [created_at]
)
VALUES
(
    'SUBJECT_TYPE',
	'Jenis Mata Pelajaran',
    GETDATE()
);

INSERT INTO [SMP_IT_HANIYA].[dbo].[mst_detail_settings]
(
	[detail_id],
	[header_id],
    [item_code],
    [item_name],
    [item_desc],
    [status],
    [item_type],
    [created_at],
	[updated_at],
    [created_by],
    [updated_by]
)
VALUES
(
    'SUBJ_PRIMARY',
    'SUBJECT_TYPE',
	'PRIMARY',
    'PRIMARY',
	'MUATAN UTAMA',
    'ACTIVE',
    'text',
    GETDATE(),
	GETDATE(),
    'admin',
    'admin'
),(
    'SUBJ_LOCAL',
    'SUBJECT_TYPE',
	'LOCAL',
    'LOCAL',
	'MUATAN LOKAL',
    'ACTIVE',
    'text',
    GETDATE(),
	GETDATE(),
    'admin',
    'admin'
),(
    'SUBJ_ISLAMIC',
    'SUBJECT_TYPE',
	'ISLAMIC',
    'ISLAMIC',
	'MUATAN ISLAM',
    'ACTIVE',
    'text',
    GETDATE(),
	GETDATE(),
    'admin',
    'admin'
);

ALTER TABLE [dbo].[mst_subjects]
ADD [subject_type] VARCHAR(50) NULL;

CREATE TABLE [dbo].[txn_eraports](
    [eraport_id] VARCHAR(20) NOT NULL,
    [student_id] VARCHAR(20) NOT NULL,
    [student_name] VARCHAR(100) NOT NULL,
    [nis] VARCHAR(20) NULL,
    [class_id] VARCHAR(20) NULL,
    [class_name] VARCHAR(20) NULL,
    [semester] VARCHAR(10) NULL,
    [academic_year_id] VARCHAR(20) NOT NULL,
    [academic_year_name] VARCHAR(20) NOT NULL,
    [school_name] VARCHAR(150) NULL,
    [school_address] VARCHAR(255) NULL,

    [homeroom_teacher_npk] VARCHAR(20) NULL,
	[homeroom_teacher_name] VARCHAR(100) NULL,

	[headschool_name] VARCHAR(100) NULL,
	[headschool_npk] VARCHAR(100) NULL,

    [parent_name] VARCHAR(150) NULL,

    [homeroom_teacher_notes] VARCHAR(MAX) NULL,
    [parent_notes] VARCHAR(MAX) NULL,
    [kokurikuler] VARCHAR(MAX) NULL,

    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL,
    [created_by] VARCHAR(50) NULL,
    [updated_by] VARCHAR(50) NULL,

PRIMARY KEY CLUSTERED ([eraport_id])
);

CREATE TABLE [dbo].[txn_eraport_grades](
    [eraport_grade_id] VARCHAR(20) NOT NULL,
    [eraport_id] VARCHAR(20) NOT NULL,

    [subject_id] VARCHAR(20) NULL,
    [subject_name] VARCHAR(100) NOT NULL,

    [subject_type_id] VARCHAR(50) NOT NULL,
	[subject_type_name] VARCHAR(100) NOT NULL,

    [final_score_rps] DECIMAL(5,2) NULL,
	[final_score_adjustment] DECIMAL(5,2) NULL,
    [predicate] VARCHAR(10) NULL,

    [competency_description] VARCHAR(MAX) NULL,

    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL,
    [created_by] VARCHAR(50) NULL,
    [updated_by] VARCHAR(50) NULL,

PRIMARY KEY CLUSTERED ([eraport_grade_id]),

FOREIGN KEY ([eraport_id]) 
REFERENCES [dbo].[txn_eraports]([eraport_id])
);

CREATE TABLE [dbo].[txn_eraport_attendances](
    [eraport_attendance_id] VARCHAR(20) NOT NULL,
    [eraport_id] VARCHAR(20) NOT NULL,

    [attendance_type_id] VARCHAR(50) NOT NULL,
	[attendance_type_name] VARCHAR(100) NOT NULL,

    [total_days] INT NOT NULL,

    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL,
    [created_by] VARCHAR(50) NULL,
    [updated_by] VARCHAR(50) NULL,

PRIMARY KEY CLUSTERED ([eraport_attendance_id]),

FOREIGN KEY ([eraport_id]) 
REFERENCES [dbo].[txn_eraports]([eraport_id])
);

CREATE TABLE [dbo].[txn_eraport_extracurriculars](
    [eraport_extracurricular_id] VARCHAR(20) NOT NULL,
    [eraport_id] VARCHAR(20) NOT NULL,

	[extracurricular_id] VARCHAR(20) NOT NULL,
    [extracurricular_name] VARCHAR(100) NOT NULL,

    [predicate] VARCHAR(10) NULL,

    [description] VARCHAR(MAX) NULL,

    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL,
    [created_by] VARCHAR(50) NULL,
    [updated_by] VARCHAR(50) NULL,

PRIMARY KEY CLUSTERED ([eraport_extracurricular_id]),

FOREIGN KEY ([eraport_id]) 
REFERENCES [dbo].[txn_eraports]([eraport_id])
);