create table "table" ("id" uuid not null, "created_at" timestamp(0) without time zone not null default CURRENT_TIMESTAMP);

alter table "table" add primary key ("id");

alter table "table" add column "content" text not null;

