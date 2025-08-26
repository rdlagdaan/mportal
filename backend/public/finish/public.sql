/*
 Navicat Premium Dump SQL

 Source Server         : MPORTAL
 Source Server Type    : PostgreSQL
 Source Server Version : 150013 (150013)
 Source Host           : localhost:5434
 Source Catalog        : mportal_db
 Source Schema         : public

 Target Server Type    : PostgreSQL
 Target Server Version : 150013 (150013)
 File Encoding         : 65001

 Date: 11/08/2025 09:35:34
*/


-- ----------------------------
-- Sequence structure for failed_jobs_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."failed_jobs_id_seq";
CREATE SEQUENCE "public"."failed_jobs_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

-- ----------------------------
-- Sequence structure for jobs_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."jobs_id_seq";
CREATE SEQUENCE "public"."jobs_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

-- ----------------------------
-- Sequence structure for migrations_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."migrations_id_seq";
CREATE SEQUENCE "public"."migrations_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 2147483647
START 1
CACHE 1;

-- ----------------------------
-- Sequence structure for permissions_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."permissions_id_seq";
CREATE SEQUENCE "public"."permissions_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

-- ----------------------------
-- Sequence structure for personal_access_tokens_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."personal_access_tokens_id_seq";
CREATE SEQUENCE "public"."personal_access_tokens_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

-- ----------------------------
-- Sequence structure for roles_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."roles_id_seq";
CREATE SEQUENCE "public"."roles_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

-- ----------------------------
-- Sequence structure for users_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."users_id_seq";
CREATE SEQUENCE "public"."users_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS "public"."cache";
CREATE TABLE "public"."cache" (
  "key" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "value" text COLLATE "pg_catalog"."default" NOT NULL,
  "expiration" int4 NOT NULL
)
;

-- ----------------------------
-- Records of cache
-- ----------------------------
INSERT INTO "public"."cache" VALUES ('laravel-cache-b7ad7f2b04bd98f199a2b8c016e37e66c831b866:timer', 'i:1754815224;', 1754815224);
INSERT INTO "public"."cache" VALUES ('laravel-cache-b7ad7f2b04bd98f199a2b8c016e37e66c831b866', 'i:1;', 1754815224);

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS "public"."cache_locks";
CREATE TABLE "public"."cache_locks" (
  "key" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "owner" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "expiration" int4 NOT NULL
)
;

-- ----------------------------
-- Records of cache_locks
-- ----------------------------

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS "public"."failed_jobs";
CREATE TABLE "public"."failed_jobs" (
  "id" int8 NOT NULL DEFAULT nextval('failed_jobs_id_seq'::regclass),
  "uuid" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "connection" text COLLATE "pg_catalog"."default" NOT NULL,
  "queue" text COLLATE "pg_catalog"."default" NOT NULL,
  "payload" text COLLATE "pg_catalog"."default" NOT NULL,
  "exception" text COLLATE "pg_catalog"."default" NOT NULL,
  "failed_at" timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
)
;

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS "public"."job_batches";
CREATE TABLE "public"."job_batches" (
  "id" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "name" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "total_jobs" int4 NOT NULL,
  "pending_jobs" int4 NOT NULL,
  "failed_jobs" int4 NOT NULL,
  "failed_job_ids" text COLLATE "pg_catalog"."default" NOT NULL,
  "options" text COLLATE "pg_catalog"."default",
  "cancelled_at" int4,
  "created_at" int4 NOT NULL,
  "finished_at" int4
)
;

-- ----------------------------
-- Records of job_batches
-- ----------------------------

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS "public"."jobs";
CREATE TABLE "public"."jobs" (
  "id" int8 NOT NULL DEFAULT nextval('jobs_id_seq'::regclass),
  "queue" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "payload" text COLLATE "pg_catalog"."default" NOT NULL,
  "attempts" int2 NOT NULL,
  "reserved_at" int4,
  "available_at" int4 NOT NULL,
  "created_at" int4 NOT NULL
)
;

-- ----------------------------
-- Records of jobs
-- ----------------------------

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS "public"."migrations";
CREATE TABLE "public"."migrations" (
  "id" int4 NOT NULL DEFAULT nextval('migrations_id_seq'::regclass),
  "migration" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "batch" int4 NOT NULL
)
;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO "public"."migrations" VALUES (1, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO "public"."migrations" VALUES (2, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO "public"."migrations" VALUES (3, '0001_01_01_000004_create_password_reset_tokens_table', 1);
INSERT INTO "public"."migrations" VALUES (4, '2025_07_15_115320_create_personal_access_tokens_table', 1);
INSERT INTO "public"."migrations" VALUES (5, '2025_08_09_182733_create_permission_tables', 1);
INSERT INTO "public"."migrations" VALUES (6, '2025_08_09_183000_create_users_table', 1);

-- ----------------------------
-- Table structure for model_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS "public"."model_has_permissions";
CREATE TABLE "public"."model_has_permissions" (
  "permission_id" int8 NOT NULL,
  "model_type" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "model_id" int8 NOT NULL
)
;

-- ----------------------------
-- Records of model_has_permissions
-- ----------------------------

-- ----------------------------
-- Table structure for model_has_roles
-- ----------------------------
DROP TABLE IF EXISTS "public"."model_has_roles";
CREATE TABLE "public"."model_has_roles" (
  "role_id" int8 NOT NULL,
  "model_type" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "model_id" int8 NOT NULL
)
;

-- ----------------------------
-- Records of model_has_roles
-- ----------------------------
INSERT INTO "public"."model_has_roles" VALUES (6, 'App\Models\User', 16);

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS "public"."password_reset_tokens";
CREATE TABLE "public"."password_reset_tokens" (
  "email" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "token" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "created_at" timestamp(0)
)
;

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS "public"."permissions";
CREATE TABLE "public"."permissions" (
  "id" int8 NOT NULL DEFAULT nextval('permissions_id_seq'::regclass),
  "name" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "guard_name" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "created_at" timestamp(0),
  "updated_at" timestamp(0)
)
;

-- ----------------------------
-- Records of permissions
-- ----------------------------

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS "public"."personal_access_tokens";
CREATE TABLE "public"."personal_access_tokens" (
  "id" int8 NOT NULL DEFAULT nextval('personal_access_tokens_id_seq'::regclass),
  "tokenable_type" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "tokenable_id" int8 NOT NULL,
  "name" text COLLATE "pg_catalog"."default" NOT NULL,
  "token" varchar(64) COLLATE "pg_catalog"."default" NOT NULL,
  "abilities" text COLLATE "pg_catalog"."default",
  "last_used_at" timestamp(0),
  "expires_at" timestamp(0),
  "created_at" timestamp(0),
  "updated_at" timestamp(0)
)
;

-- ----------------------------
-- Records of personal_access_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for role_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS "public"."role_has_permissions";
CREATE TABLE "public"."role_has_permissions" (
  "permission_id" int8 NOT NULL,
  "role_id" int8 NOT NULL
)
;

-- ----------------------------
-- Records of role_has_permissions
-- ----------------------------

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS "public"."roles";
CREATE TABLE "public"."roles" (
  "id" int8 NOT NULL DEFAULT nextval('roles_id_seq'::regclass),
  "name" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "guard_name" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "created_at" timestamp(0),
  "updated_at" timestamp(0)
)
;

-- ----------------------------
-- Records of roles
-- ----------------------------
INSERT INTO "public"."roles" VALUES (6, 'micro_applicant', 'web', '2025-08-10 08:37:39', '2025-08-10 08:37:39');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS "public"."users";
CREATE TABLE "public"."users" (
  "id" int8 NOT NULL DEFAULT nextval('users_id_seq'::regclass),
  "name" varchar(120) COLLATE "pg_catalog"."default",
  "email" varchar(150) COLLATE "pg_catalog"."default" NOT NULL,
  "mobile_number" varchar(30) COLLATE "pg_catalog"."default",
  "email_verified_at" timestamp(0),
  "password" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "remember_token" varchar(100) COLLATE "pg_catalog"."default",
  "created_at" timestamp(0),
  "updated_at" timestamp(0)
)
;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO "public"."users" VALUES (16, 'Randy Lagdaan', 'rdlagdaan@gmail.com', '091757877809', NULL, '$2y$12$5lxI/plEI/rhL3DrDynoS.PQol20V8gtz/YYBRh3ecimTJaobnFeq', NULL, '2025-08-10 08:39:24', '2025-08-10 08:39:24');

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."failed_jobs_id_seq"
OWNED BY "public"."failed_jobs"."id";
SELECT setval('"public"."failed_jobs_id_seq"', 1, false);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."jobs_id_seq"
OWNED BY "public"."jobs"."id";
SELECT setval('"public"."jobs_id_seq"', 1, false);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."migrations_id_seq"
OWNED BY "public"."migrations"."id";
SELECT setval('"public"."migrations_id_seq"', 6, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."permissions_id_seq"
OWNED BY "public"."permissions"."id";
SELECT setval('"public"."permissions_id_seq"', 15, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."personal_access_tokens_id_seq"
OWNED BY "public"."personal_access_tokens"."id";
SELECT setval('"public"."personal_access_tokens_id_seq"', 1, false);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."roles_id_seq"
OWNED BY "public"."roles"."id";
SELECT setval('"public"."roles_id_seq"', 6, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."users_id_seq"
OWNED BY "public"."users"."id";
SELECT setval('"public"."users_id_seq"', 16, true);

-- ----------------------------
-- Primary Key structure for table cache
-- ----------------------------
ALTER TABLE "public"."cache" ADD CONSTRAINT "cache_pkey" PRIMARY KEY ("key");

-- ----------------------------
-- Primary Key structure for table cache_locks
-- ----------------------------
ALTER TABLE "public"."cache_locks" ADD CONSTRAINT "cache_locks_pkey" PRIMARY KEY ("key");

-- ----------------------------
-- Uniques structure for table failed_jobs
-- ----------------------------
ALTER TABLE "public"."failed_jobs" ADD CONSTRAINT "failed_jobs_uuid_unique" UNIQUE ("uuid");

-- ----------------------------
-- Primary Key structure for table failed_jobs
-- ----------------------------
ALTER TABLE "public"."failed_jobs" ADD CONSTRAINT "failed_jobs_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Primary Key structure for table job_batches
-- ----------------------------
ALTER TABLE "public"."job_batches" ADD CONSTRAINT "job_batches_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Indexes structure for table jobs
-- ----------------------------
CREATE INDEX "jobs_queue_index" ON "public"."jobs" USING btree (
  "queue" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table jobs
-- ----------------------------
ALTER TABLE "public"."jobs" ADD CONSTRAINT "jobs_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Primary Key structure for table migrations
-- ----------------------------
ALTER TABLE "public"."migrations" ADD CONSTRAINT "migrations_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Indexes structure for table model_has_permissions
-- ----------------------------
CREATE INDEX "model_has_permissions_model_id_model_type_index" ON "public"."model_has_permissions" USING btree (
  "model_id" "pg_catalog"."int8_ops" ASC NULLS LAST,
  "model_type" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table model_has_permissions
-- ----------------------------
ALTER TABLE "public"."model_has_permissions" ADD CONSTRAINT "model_has_permissions_pkey" PRIMARY KEY ("permission_id", "model_id", "model_type");

-- ----------------------------
-- Indexes structure for table model_has_roles
-- ----------------------------
CREATE INDEX "model_has_roles_model_id_model_type_index" ON "public"."model_has_roles" USING btree (
  "model_id" "pg_catalog"."int8_ops" ASC NULLS LAST,
  "model_type" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table model_has_roles
-- ----------------------------
ALTER TABLE "public"."model_has_roles" ADD CONSTRAINT "model_has_roles_pkey" PRIMARY KEY ("role_id", "model_id", "model_type");

-- ----------------------------
-- Indexes structure for table password_reset_tokens
-- ----------------------------
CREATE INDEX "password_reset_tokens_email_index" ON "public"."password_reset_tokens" USING btree (
  "email" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST
);

-- ----------------------------
-- Uniques structure for table permissions
-- ----------------------------
ALTER TABLE "public"."permissions" ADD CONSTRAINT "permissions_name_guard_name_unique" UNIQUE ("name", "guard_name");

-- ----------------------------
-- Primary Key structure for table permissions
-- ----------------------------
ALTER TABLE "public"."permissions" ADD CONSTRAINT "permissions_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Indexes structure for table personal_access_tokens
-- ----------------------------
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" ON "public"."personal_access_tokens" USING btree (
  "tokenable_type" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST,
  "tokenable_id" "pg_catalog"."int8_ops" ASC NULLS LAST
);

-- ----------------------------
-- Uniques structure for table personal_access_tokens
-- ----------------------------
ALTER TABLE "public"."personal_access_tokens" ADD CONSTRAINT "personal_access_tokens_token_unique" UNIQUE ("token");

-- ----------------------------
-- Primary Key structure for table personal_access_tokens
-- ----------------------------
ALTER TABLE "public"."personal_access_tokens" ADD CONSTRAINT "personal_access_tokens_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Primary Key structure for table role_has_permissions
-- ----------------------------
ALTER TABLE "public"."role_has_permissions" ADD CONSTRAINT "role_has_permissions_pkey" PRIMARY KEY ("permission_id", "role_id");

-- ----------------------------
-- Uniques structure for table roles
-- ----------------------------
ALTER TABLE "public"."roles" ADD CONSTRAINT "roles_name_guard_name_unique" UNIQUE ("name", "guard_name");

-- ----------------------------
-- Primary Key structure for table roles
-- ----------------------------
ALTER TABLE "public"."roles" ADD CONSTRAINT "roles_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Uniques structure for table users
-- ----------------------------
ALTER TABLE "public"."users" ADD CONSTRAINT "users_email_unique" UNIQUE ("email");

-- ----------------------------
-- Primary Key structure for table users
-- ----------------------------
ALTER TABLE "public"."users" ADD CONSTRAINT "users_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Foreign Keys structure for table model_has_permissions
-- ----------------------------
ALTER TABLE "public"."model_has_permissions" ADD CONSTRAINT "model_has_permissions_permission_id_foreign" FOREIGN KEY ("permission_id") REFERENCES "public"."permissions" ("id") ON DELETE CASCADE ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table model_has_roles
-- ----------------------------
ALTER TABLE "public"."model_has_roles" ADD CONSTRAINT "model_has_roles_role_id_foreign" FOREIGN KEY ("role_id") REFERENCES "public"."roles" ("id") ON DELETE CASCADE ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table role_has_permissions
-- ----------------------------
ALTER TABLE "public"."role_has_permissions" ADD CONSTRAINT "role_has_permissions_permission_id_foreign" FOREIGN KEY ("permission_id") REFERENCES "public"."permissions" ("id") ON DELETE CASCADE ON UPDATE NO ACTION;
ALTER TABLE "public"."role_has_permissions" ADD CONSTRAINT "role_has_permissions_role_id_foreign" FOREIGN KEY ("role_id") REFERENCES "public"."roles" ("id") ON DELETE CASCADE ON UPDATE NO ACTION;
