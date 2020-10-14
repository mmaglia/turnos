----------------------------------------------------------------------
-- CREADOS Y EJECUTADOS EN PRODUCCIÓN EL 14/10/2020 EN LOS 3 TURNEROS
----------------------------------------------------------------------
DROP INDEX public.idx_oficina_habilitada;
DROP INDEX public.idx_oficina_oficina;
DROP INDEX public.idx_turno_estado;
DROP INDEX public.idx_turno_fecha_hora;
DROP INDEX public.idx_turno_rechazado_fecha_hora_turno;
DROP INDEX public.idx_turno_rechazado_fecha_hora_rechazo;
DROP INDEX public.idx_turnos_diarios_fecha;

CREATE INDEX idx_oficina_habilitada
    ON public.oficina USING btree
    (habilitada ASC NULLS LAST)
    TABLESPACE pg_default;

CREATE INDEX idx_oficina_oficina
    ON public.oficina USING btree
    (oficina COLLATE pg_catalog."default" ASC NULLS LAST)
    TABLESPACE pg_default;

CREATE INDEX idx_turno_estado
    ON public.turno USING btree
    (estado ASC NULLS LAST)
    TABLESPACE pg_default;

CREATE INDEX idx_turno_fecha_hora
    ON public.turno USING btree
    (fecha_hora ASC NULLS LAST)
    TABLESPACE pg_default;	

CREATE INDEX idx_turno_rechazado_fecha_hora_turno
    ON public.turno_rechazado USING btree
    (fecha_hora_turno ASC NULLS LAST)
    TABLESPACE pg_default;	

CREATE INDEX idx_turno_rechazado_fecha_hora_rechazo
    ON public.turno_rechazado USING btree
    (fecha_hora_rechazo ASC NULLS LAST)
    TABLESPACE pg_default;	
	
CREATE INDEX idx_turnos_diarios_fecha
    ON public.turnos_diarios USING btree
    (fecha ASC NULLS LAST)
    TABLESPACE pg_default;	
