-- NB 
you might want to change 
-- 
-- PostgreSQL database dump
-- Dumped from database version 13.5
-- Dumped by pg_dump version 13.5


SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

DROP DATABASE ongevallen;
--
-- TOC entry 3028 (class 1262 OID 47403)
-- Name: ongevallen; Type: DATABASE; Schema: -; Owner: ongevallen
--

CREATE DATABASE ongevallen WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE = 'en_US.utf8';


ALTER DATABASE ongevallen OWNER TO ongevallen;

\connect ongevallen

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 206 (class 1255 OID 47538)
-- Name: fn_set_lat_lng(); Type: FUNCTION; Schema: public; Owner: ongevallen
--

CREATE FUNCTION public.fn_set_lat_lng() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF NEW.latlng IS NULL THEN
    NEW.latlng = point(NEW.lat, NEW.lng);
  END IF;
  RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_set_lat_lng() OWNER TO ongevallen;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 201 (class 1259 OID 47409)
-- Name: aardongevallen; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.aardongevallen (
    "AOL_ID" smallint NOT NULL,
    "AOL_OMS" character varying(25) NOT NULL
);


ALTER TABLE public.aardongevallen OWNER TO ongevallen;

--
-- TOC entry 202 (class 1259 OID 47420)
-- Name: aflopen; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.aflopen (
    "AP3_CODE" character(3) NOT NULL,
    "AP3_OMS" character varying(30) NOT NULL
);


ALTER TABLE public.aflopen OWNER TO ongevallen;

--
-- TOC entry 204 (class 1259 OID 47540)
-- Name: gemeentes; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.gemeentes (
    "GME_NAAM" character varying(80) NOT NULL,
    lat numeric(12,10) NOT NULL,
    lng numeric(12,10) NOT NULL,
    flag character varying(150)
);


ALTER TABLE public.gemeentes OWNER TO ongevallen;

--
-- TOC entry 200 (class 1259 OID 47404)
-- Name: ongevallen; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.ongevallen (
    "VKL_NUMMER" bigint NOT NULL,
    "JAAR_VKL" smallint NOT NULL,
    "AP3_CODE" character(3),
    "AOL_ID" smallint,
    "WSE_ID" smallint,
    "BEBKOM" character(2),
    "MAXSNELHD" smallint,
    "GME_ID" smallint NOT NULL,
    "GME_NAAM" character varying(80) NOT NULL,
    "PVE_CODE" character(2),
    lat numeric(8,6) NOT NULL,
    lng numeric(8,6) NOT NULL,
    latlng point
);


ALTER TABLE public.ongevallen OWNER TO ongevallen;

--
-- TOC entry 205 (class 1259 OID 47546)
-- Name: provincies; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.provincies (
    "PVE_CODE" character(2) NOT NULL,
    "PVE_OMS" character varying(20) NOT NULL,
    lat numeric(12,10) NOT NULL,
    lng numeric(12,10) NOT NULL
);


ALTER TABLE public.provincies OWNER TO ongevallen;

--
-- TOC entry 203 (class 1259 OID 47431)
-- Name: wegsituaties; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.wegsituaties (
    "WSE_ID" smallint NOT NULL,
    "WSE_OMS" character varying(35) NOT NULL
);


ALTER TABLE public.wegsituaties OWNER TO ongevallen;

--
-- TOC entry 2879 (class 2606 OID 47413)
-- Name: aardongevallen aard_ongeval_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.aardongevallen
    ADD CONSTRAINT aard_ongeval_pkey PRIMARY KEY ("AOL_ID");


--
-- TOC entry 2881 (class 2606 OID 47424)
-- Name: aflopen aflopen_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.aflopen
    ADD CONSTRAINT aflopen_pkey PRIMARY KEY ("AP3_CODE");


--
-- TOC entry 2885 (class 2606 OID 47544)
-- Name: gemeentes gemeentes_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.gemeentes
    ADD CONSTRAINT gemeentes_pkey PRIMARY KEY ("GME_NAAM");


--
-- TOC entry 2877 (class 2606 OID 47454)
-- Name: ongevallen ongevallen_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.ongevallen
    ADD CONSTRAINT ongevallen_pkey PRIMARY KEY ("VKL_NUMMER");


--
-- TOC entry 2888 (class 2606 OID 47550)
-- Name: provincies provincies_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.provincies
    ADD CONSTRAINT provincies_pkey PRIMARY KEY ("PVE_CODE");


--
-- TOC entry 2883 (class 2606 OID 47435)
-- Name: wegsituaties wegsituaties_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.wegsituaties
    ADD CONSTRAINT wegsituaties_pkey PRIMARY KEY ("WSE_ID");


--
-- TOC entry 2871 (class 1259 OID 47419)
-- Name: fki_fK_aard_ongeval; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX "fki_fK_aard_ongeval" ON public.ongevallen USING btree ("AOL_ID");


--
-- TOC entry 2872 (class 1259 OID 47430)
-- Name: fki_fk_aflopen; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX fki_fk_aflopen ON public.ongevallen USING btree ("AP3_CODE");


--
-- TOC entry 2873 (class 1259 OID 47441)
-- Name: fki_fk_wegsituaties; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX fki_fk_wegsituaties ON public.ongevallen USING btree ("WSE_ID");


--
-- TOC entry 2874 (class 1259 OID 47545)
-- Name: ix_GME_NAAM; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX "ix_GME_NAAM" ON public.ongevallen USING btree ("GME_NAAM");


--
-- TOC entry 2886 (class 1259 OID 47551)
-- Name: ix_PR_OMS; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX "ix_PR_OMS" ON public.provincies USING btree ("PVE_OMS");


--
-- TOC entry 2875 (class 1259 OID 47552)
-- Name: ix_PV_CODE; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX "ix_PV_CODE" ON public.ongevallen USING btree ("PVE_CODE");


--
-- TOC entry 2892 (class 2620 OID 47539)
-- Name: ongevallen tr_set_lat_lng; Type: TRIGGER; Schema: public; Owner: ongevallen
--

CREATE TRIGGER tr_set_lat_lng BEFORE INSERT OR UPDATE ON public.ongevallen FOR EACH ROW EXECUTE FUNCTION public.fn_set_lat_lng();


--
-- TOC entry 2891 (class 2606 OID 47414)
-- Name: ongevallen fk_aard_ongeval; Type: FK CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.ongevallen
    ADD CONSTRAINT fk_aard_ongeval FOREIGN KEY ("AOL_ID") REFERENCES public.aardongevallen("AOL_ID") ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID;


--
-- TOC entry 2889 (class 2606 OID 47425)
-- Name: ongevallen fk_aflopen; Type: FK CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.ongevallen
    ADD CONSTRAINT fk_aflopen FOREIGN KEY ("AP3_CODE") REFERENCES public.aflopen("AP3_CODE") ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID;


--
-- TOC entry 2890 (class 2606 OID 47436)
-- Name: ongevallen fk_wegsituaties; Type: FK CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.ongevallen
    ADD CONSTRAINT fk_wegsituaties FOREIGN KEY ("WSE_ID") REFERENCES public.wegsituaties("WSE_ID") ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID;


-- Completed on 2022-05-25 11:22:45 UTC

--
-- PostgreSQL database dump complete
--

