drop table if exists line_item;
drop table if exists library_item;
drop table if exists orders;
drop table if exists basket;
drop table if exists person;
drop table if exists promotion_voucher_multi;
drop table if exists promotion_criteria;
drop table if exists promotion_outcome;
drop table if exists promotion;

drop sequence if exists order_id_seq;
drop sequence if exists promotion_criteria_id_seq;
drop sequence if exists promotion_outcome_id_seq;

CREATE TABLE person
(
	id integer NOT NULL,
	registration_source_id integer,
	email character varying(255) NOT NULL,
	password character varying(60) DEFAULT NULL::character varying,
	auth_level integer NOT NULL DEFAULT 1,
	first_name character varying(128) NOT NULL,
	last_name character varying(128) NOT NULL,
	display_name character varying(128) DEFAULT NULL::character varying,
	registration_time timestamp(0) without time zone NOT NULL,
	nectar_artifact json NOT NULL,
	nectar_balance integer,
	facebook_user_id character varying(255) DEFAULT NULL::character varying,
	deleted boolean NOT NULL DEFAULT false,
	deleted_time timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
	CONSTRAINT person_pkey PRIMARY KEY (id)
);


CREATE TABLE basket
(
	id integer NOT NULL,
	basket_created_time timestamp(0) without time zone NOT NULL,
	order_state_discriminator character varying(255) NOT NULL,
	CONSTRAINT basket_pkey PRIMARY KEY (id)
);

CREATE TABLE orders
(
	id integer NOT NULL,
	person_id integer,
	order_complete_time timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
	vat_rate double precision NOT NULL,
	discount_inc_vat double precision NOT NULL,
	subtotal_inc_vat double precision NOT NULL,
	total_inc_vat double precision NOT NULL,
	nectar_card_number character varying(255) DEFAULT NULL::character varying,
	nectar_base_points integer,
	nectar_bonus_points integer,
	nectar_selling_price integer,
	payment_provider character varying(255) DEFAULT NULL::character varying,
	payment_reference character varying(255) DEFAULT NULL::character varying,
	payment_misc character varying(255) DEFAULT NULL::character varying,
	from_frisk boolean NOT NULL DEFAULT false,
	CONSTRAINT orders_pkey PRIMARY KEY (id),
	CONSTRAINT fk_e52ffdee217bbb47 FOREIGN KEY (person_id)
	REFERENCES person (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
	CONSTRAINT fk_e52ffdeebf396750 FOREIGN KEY (id)
	REFERENCES basket (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE CASCADE
);

CREATE TABLE line_item
(
	id uuid NOT NULL, -- (DC2Type:uuid)
	basket_id integer,
	refund_id integer,
	product_sku character varying(255) NOT NULL,
	cost_price_inc_vat double precision NOT NULL,
	sales_price_inc_vat double precision NOT NULL,
	cost_price_pre_override_inc_vat double precision,
	sales_price_pre_override_inc_vat double precision,
	cost_price_pre_promotion_inc_vat double precision,
	sales_price_pre_promotion_inc_vat double precision,
	rrp_inc_vat double precision NOT NULL,
	nectar_base_points integer,
	nectar_bonus_points integer,
	nectar_selling_price integer,
	status integer,
	supplier_short_name character varying(255) NOT NULL,
	voucher character varying(255) DEFAULT NULL::character varying,
	product_type character varying(255) NOT NULL DEFAULT 'book'::character varying,
	recur boolean,
	remote_purchase_id integer, -- If the line item is registered on a remote system, then the id from the remote system. e.g. 7 digital purchase id
	parent_product_sku character varying(255) DEFAULT NULL::character varying, -- If the item is a music track then we store the sku of the release sku. Can be used with other products that have a parent-child relationship
	date_added timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
	CONSTRAINT line_item_pkey PRIMARY KEY (id),
	CONSTRAINT fk_9456d6c71be1fb52 FOREIGN KEY (basket_id)
	REFERENCES basket (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE library_item
(
	id serial NOT NULL,
	person_id integer,
	product_sku character varying(255) NOT NULL,
	reading_location_url character varying(255) DEFAULT NULL::character varying,
	reading_location_fraction double precision,
	date_added timestamp(0) without time zone NOT NULL DEFAULT now(),
	date_modified timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
	client_modified_date timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
	legacy_book_id integer,
	downloaded_amount integer NOT NULL DEFAULT 0,
	from_frisk boolean NOT NULL DEFAULT false,
	CONSTRAINT library_item_pkey PRIMARY KEY (id),
	CONSTRAINT fk_b9d4ef73217bbb47 FOREIGN KEY (person_id)
	REFERENCES person (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE promotion
(
	id integer NOT NULL,
	name character varying(100) NOT NULL,
	start_time timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
	end_time timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
	description character varying(255) DEFAULT NULL::character varying,
	image_url character varying(200) DEFAULT NULL::character varying,
	exclusive boolean NOT NULL DEFAULT false,
	current_uses integer NOT NULL DEFAULT 0,
	max_uses integer,
	family character varying(30) DEFAULT NULL::character varying,
	priority integer NOT NULL DEFAULT 0,
	CONSTRAINT promotion_pkey PRIMARY KEY (id)
);

CREATE TABLE promotion_criteria
(
	id integer NOT NULL,
	promotion_id integer,
	options json,
	near_miss_threshold integer,
	near_miss_message character varying(255) DEFAULT NULL::character varying,
	type character varying(255) NOT NULL,
	CONSTRAINT promotion_criteria_pkey PRIMARY KEY (id),
	CONSTRAINT fk_704b3fc5139df194 FOREIGN KEY (promotion_id)
	REFERENCES promotion (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE promotion_outcome
(
	id integer NOT NULL,
	promotion_id integer,
	message character varying(255) DEFAULT NULL::character varying,
	options json,
	sort_order integer NOT NULL DEFAULT 0,
	type character varying(255) NOT NULL,
	CONSTRAINT promotion_outcome_pkey PRIMARY KEY (id),
	CONSTRAINT fk_68c19bf8139df194 FOREIGN KEY (promotion_id)
	REFERENCES promotion (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE promotion_voucher_multi
(
	id integer NOT NULL,
	code character varying(100) NOT NULL,
	total_allowed_uses integer,
	use_count integer,
	CONSTRAINT promotion_voucher_multi_pkey PRIMARY KEY (id),
	CONSTRAINT fk_99459344bf396750 FOREIGN KEY (id)
	REFERENCES promotion_criteria (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE CASCADE
);

CREATE SEQUENCE order_id_seq
INCREMENT 1
MINVALUE 300000
MAXVALUE 9223372036854775807
START 300001
CACHE 1;

CREATE SEQUENCE promotion_criteria_id_seq
INCREMENT 1
MINVALUE 300000
MAXVALUE 9223372036854775807
START 300001
CACHE 1;

CREATE SEQUENCE promotion_outcome_id_seq
INCREMENT 1
MINVALUE 300000
MAXVALUE 9223372036854775807
START 300001
CACHE 1;


ALTER TABLE person OWNER TO vagrant;
ALTER TABLE basket OWNER TO vagrant;
ALTER TABLE orders OWNER TO vagrant;
ALTER TABLE line_item OWNER TO vagrant;
ALTER TABLE library_item OWNER TO vagrant;
ALTER TABLE promotion OWNER TO vagrant;
ALTER TABLE promotion_criteria OWNER TO vagrant;
ALTER TABLE promotion_outcome OWNER TO vagrant;
ALTER TABLE promotion_voucher_multi OWNER TO vagrant;

INSERT INTO person
(id, registration_source_id, email, password, auth_level, first_name, last_name, display_name, registration_time, nectar_artifact, nectar_balance, facebook_user_id, deleted, deleted_time)
VALUES (1, 1, 'test.001@ebs.io', '$2y$10$MksmgRsC9zqCwT2tqe0Q8e7iRH5gL6T.eJGx3EevLoZRZ433FERs6', 2, 'Tester', 'One', 'Tester 1', '2014-05-07 13:45:03', '{}', 2000, null, false, null);
