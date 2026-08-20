# osTicket Time Tracking — registro de horas

Registra el tiempo dedicado a cada respuesta y nota interna, con tipo de tiempo
y marca de facturable. Lo muestra en pantalla, lo exporta a CSV y lo expone por
API REST.

Hecho para **osTicket 1.18.x**. Probado en 1.18.4 con PHP 8.2 y MariaDB 11.8.

## Por qué otro

La propuesta histórica del upstream para esta funcionalidad ([PR #3231]) son
1.370 líneas de parches sobre 24 archivos del core, está abierta desde 2016 y
nunca se mergeó. Parchear el core significa reaplicar esos cambios después de
cada upgrade, y perder el registro de horas en silencio el día que te olvidás.

Este plugin hace lo contrario: **no toca el core** y guarda los datos en una
tabla propia, así ningún upgrade de osTicket puede romperlos ni borrarlos.

[PR #3231]: https://github.com/osTicket/osTicket/pull/3231

## Qué hace

- Un **campo de tiempo** en los formularios de respuesta y nota interna. Acepta
  `90`, `1:30`, `1h30`, `2h`, `1,5h` o `45m`: lo que al agente le salga natural.
- **Tipos de tiempo** (Remoto, On-site, Viaje, Taller — configurables) y casilla
  de **facturable**.
- **Redondeo** opcional (5/15/30/60 minutos, hacia arriba) y **tiempo
  obligatorio**.
- Un **badge en cada entrada del hilo** y el **total en la ficha del ticket**.
- Un **reporte** con filtros por período, organización, agente, tipo y
  facturable, con desgloses por organización y por tipo.
- **Exportación a CSV** con BOM UTF-8 y punto y coma, para que Excel en español
  lo abra bien.
- **Endpoints REST** para llevar los números a la facturación.
- **Columna de cola** opcional con el tiempo registrado por ticket.

## Instalación

```bash
unzip osticket-timetrack-1.0.0.zip
cd osticket-timetrack
sudo ./install.sh /var/www/osticket
```

Después, en el panel de administración:

1. **Admin Panel → Manage → Plugins → Add New Plugin → Time Tracking**
2. Instalarlo, abrirlo y **agregar una instancia** — eso es lo que realmente lo
   activa en osTicket 1.17 en adelante.
3. Opcional: **Manage → Ticket Queues → *tu cola* → Columns** y agregar la
   anotación *Tiempo registrado*.

La tabla `ost_timetrack` se crea sola en el primer arranque.

El instalador también deja **`scp/apps.php`**. Ese archivo no es un capricho:
osTicket arma la pestaña "Applications" apuntando ahí
(`include/class.nav.php`) pero nunca lo distribuye, así que el tab da 404 para
*cualquier* plugin que registre una aplicación. Es un archivo nuevo, no lo pisa
ningún upgrade.

## Idioma

Sale en inglés por defecto y trae castellano rioplatense. Si el agente tiene el
panel en español, el plugin lo sigue solo. Para sumar un idioma: copiar
`plugin/i18n/es_AR.php`, renombrarlo al locale y traducir. No hay nada que
compilar.

## nginx

En Apache anda sin tocar nada. En nginx hay que asegurarse de que PHP reciba
`PATH_INFO`: el reporte se sirve como `/scp/apps.php/timetrack/` y la API como
`/api/<endpoint>`. Si la API devuelve `URL not supported` con 400, o el reporte
sale en blanco, el vhost lo está descartando. Está todo en
[nginx.md](nginx.md), incluido el bloque que hay que pegar.

## Parche opcional del core

El reporte y el CSV del plugin funcionan sin tocar nada. Si además querés que
la **exportación nativa de la cola** (el link "Export" del listado) incluya las
columnas de tiempo, hay un parche de 7 líneas:

```bash
sudo ./core-patch/apply.sh /var/www/osticket
sudo ./core-patch/apply.sh --check /var/www/osticket
sudo ./core-patch/apply.sh --revert /var/www/osticket
```

**Hay que reaplicarlo después de cada upgrade de osTicket.** Es lo único de lo
que el plugin no te puede proteger, y por eso es opcional y son siete líneas.

## Desinstalación

```bash
sudo ./uninstall.sh /var/www/osticket
```

Borra los archivos y revierte el parche opcional. **Los registros de tiempo
quedan**: la tabla `ost_timetrack` se borra a mano, a propósito, para que nadie
pierda el historial de facturación por un descuido.

## Licencia

GNU General Public License v2, la misma que osTicket.
