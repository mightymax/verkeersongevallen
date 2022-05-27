--
-- PostgreSQL database dump
--

-- Dumped from database version 13.5
-- Dumped by pg_dump version 13.5

-- Started on 2022-05-27 17:13:16 UTC

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
-- TOC entry 3022 (class 1262 OID 47403)
-- Name: ongevallen; Type: DATABASE; Schema: -; Owner: ongevallen
--

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
-- TOC entry 205 (class 1255 OID 47538)
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
-- TOC entry 200 (class 1259 OID 47567)
-- Name: gemeentes; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.gemeentes (
    "PVE_NAAM" character varying(15) NOT NULL,
    "GME_NAAM" character varying(50) NOT NULL,
    pve_lat numeric(12,10) NOT NULL,
    pve_lng numeric(12,10) NOT NULL,
    pve_vlag character varying(255) NOT NULL,
    gme_lat numeric(12,10) NOT NULL,
    gme_lng numeric(12,10) NOT NULL,
    gme_vlag character varying(255)
);


ALTER TABLE public.gemeentes OWNER TO ongevallen;

--
-- TOC entry 204 (class 1259 OID 47716)
-- Name: gemeentes_stats; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.gemeentes_stats (
    "GME_NAAM" character varying(50) NOT NULL,
    "PVE_NAAM" character varying(15) NOT NULL,
    count integer NOT NULL,
    "DOD" integer NOT NULL,
    "LET" integer NOT NULL,
    "UMS" integer
);


ALTER TABLE public.gemeentes_stats OWNER TO ongevallen;

--
-- TOC entry 201 (class 1259 OID 47613)
-- Name: ongevallen; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.ongevallen (
    "VKL_NUMMER" bigint NOT NULL,
    "JAAR_VKL" smallint NOT NULL,
    "AP3_CODE" character(3),
    "GME_NAAM" character varying(50) NOT NULL,
    "PVE_NAAM" character varying(15) NOT NULL,
    "FK_VELD5" character(17) NOT NULL,
    latlng point
);


ALTER TABLE public.ongevallen OWNER TO ongevallen;

--
-- TOC entry 203 (class 1259 OID 47711)
-- Name: provincies; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.provincies (
    "PVE_NAAM" character varying(15) NOT NULL,
    pve_vlag character varying(150) NOT NULL,
    lat numeric(12,10) NOT NULL,
    lng numeric(12,10) NOT NULL,
    count integer NOT NULL,
    "LET" integer NOT NULL,
    "DOD" integer NOT NULL,
    "UMS" integer NOT NULL
);


ALTER TABLE public.provincies OWNER TO ongevallen;

--
-- TOC entry 202 (class 1259 OID 47670)
-- Name: puntlocaties; Type: TABLE; Schema: public; Owner: ongevallen
--

CREATE TABLE public.puntlocaties (
    "FK_VELD5" character(17) NOT NULL,
    "Y_COORD" numeric(12,10) NOT NULL,
    "X_COORD" numeric(12,10) NOT NULL
);


ALTER TABLE public.puntlocaties OWNER TO ongevallen;

--
-- TOC entry 2885 (class 2606 OID 47720)
-- Name: gemeentes_stats gemeente_stats_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.gemeentes_stats
    ADD CONSTRAINT gemeente_stats_pkey PRIMARY KEY ("GME_NAAM", "PVE_NAAM");


--
-- TOC entry 2869 (class 2606 OID 47592)
-- Name: gemeentes gemeentes_nieuw_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.gemeentes
    ADD CONSTRAINT gemeentes_nieuw_pkey PRIMARY KEY ("PVE_NAAM", "GME_NAAM");


--
-- TOC entry 2879 (class 2606 OID 47617)
-- Name: ongevallen ongevallen_nieuw_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.ongevallen
    ADD CONSTRAINT ongevallen_nieuw_pkey PRIMARY KEY ("VKL_NUMMER");


--
-- TOC entry 2883 (class 2606 OID 47715)
-- Name: provincies provincies_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.provincies
    ADD CONSTRAINT provincies_pkey PRIMARY KEY ("PVE_NAAM");


--
-- TOC entry 2881 (class 2606 OID 47674)
-- Name: puntlocaties puntlocaties_pkey; Type: CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.puntlocaties
    ADD CONSTRAINT puntlocaties_pkey PRIMARY KEY ("FK_VELD5");


--
-- TOC entry 2872 (class 1259 OID 47626)
-- Name: fki_gemeentes; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX fki_gemeentes ON public.ongevallen USING btree ("GME_NAAM", "PVE_NAAM");


--
-- TOC entry 2873 (class 1259 OID 47680)
-- Name: fki_puntlocaties; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX fki_puntlocaties ON public.ongevallen USING btree ("FK_VELD5");


--
-- TOC entry 2874 (class 1259 OID 47618)
-- Name: ix_ap3_code; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX ix_ap3_code ON public.ongevallen USING btree ("AP3_CODE");


--
-- TOC entry 2875 (class 1259 OID 47736)
-- Name: ix_geemeente_lc; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX ix_geemeente_lc ON public.ongevallen USING btree (lower(("GME_NAAM")::text));


--
-- TOC entry 2876 (class 1259 OID 47619)
-- Name: ix_gemeente; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX ix_gemeente ON public.ongevallen USING btree ("GME_NAAM");


--
-- TOC entry 2870 (class 1259 OID 47709)
-- Name: ix_gme_naam; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX ix_gme_naam ON public.gemeentes USING btree ("GME_NAAM");


--
-- TOC entry 2877 (class 1259 OID 47620)
-- Name: ix_provincie; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX ix_provincie ON public.ongevallen USING btree ("PVE_NAAM");


--
-- TOC entry 2871 (class 1259 OID 47710)
-- Name: ix_pve_naam; Type: INDEX; Schema: public; Owner: ongevallen
--

CREATE INDEX ix_pve_naam ON public.gemeentes USING btree ("PVE_NAAM");


--
-- TOC entry 2886 (class 2606 OID 47675)
-- Name: ongevallen puntlocaties; Type: FK CONSTRAINT; Schema: public; Owner: ongevallen
--

ALTER TABLE ONLY public.ongevallen
    ADD CONSTRAINT puntlocaties FOREIGN KEY ("FK_VELD5") REFERENCES public.puntlocaties("FK_VELD5") ON UPDATE CASCADE ON DELETE CASCADE NOT VALID;


-- Completed on 2022-05-27 17:13:16 UTC

--
-- PostgreSQL database dump complete
--

