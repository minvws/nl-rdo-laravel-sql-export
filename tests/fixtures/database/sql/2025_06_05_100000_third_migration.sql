create table "users" ("id" uuid not null, "email" varchar(255) not null, "created_at" timestamp(0) without time zone not null default CURRENT_TIMESTAMP, "updated_at" timestamp(0) without time zone null default CURRENT_TIMESTAMP, "deleted_at" timestamp(0) without time zone null);

alter table "users" add primary key ("id");

CREATE UNIQUE INDEX unique_active_email ON users(email) WHERE deleted_at IS NULL;

DROP INDEX unique_active_email;

CREATE UNIQUE INDEX unique_active_email ON users(email) WHERE deleted_at IS NULL;

