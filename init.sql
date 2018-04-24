-- we don't know how to generate schema sandbox (class Schema) :(
create table mail_types
(
  id int auto_increment
    primary key,
  name varchar(32) not null,
  code varchar(128) not null,
  sender json not null,
  subject varchar(64) null,
  attachments json null,
  bcc_recipients json null,
  charset varchar(16) default 'utf-8' not null,
  priority smallint(2) default '0' not null,
  constraint mail_types_code_uindex
  unique (code)
)
;

create table mails
(
  id int auto_increment
    primary key,
  mail_type_id int not null,
  recipient json not null,
  sender json not null,
  subject varchar(64) null,
  attachments json null,
  bcc_recipients json null,
  charset varchar(16) default 'utf-8' not null,
  date_created datetime default CURRENT_TIMESTAMP not null,
  data json null,
  hashcode varchar(32) not null,
  date_sent datetime null,
  constraint fk_mails_mail_types
  foreign key (mail_type_id) references mail_types (id)
)
;

create table mail_queue
(
  mail_id int not null
    primary key,
  priority smallint(2) default '0' not null,
  attempts_count smallint(2) default '0' not null,
  job_id char(36) null,
  constraint mail_queue_mails_id_fk
  foreign key (mail_id) references mails (id)
    on update cascade on delete cascade
)
;

create index mail_queue_job_id_index
  on mail_queue (job_id)
;

create index fk_mails_mail_types_idx
  on mails (mail_type_id)
;

