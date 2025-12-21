// ============================
// Utilidad para inicializar charts
// ============================
function initChart(elemId) {
    var el = document.getElementById(elemId);
    return el ? echarts.init(el) : null;
}

// ============================
// 1. Cantidad de Activos por Tipo
// ============================
var chartActivosTipo = initChart("chart-activos-tipo");
if (chartActivosTipo) {
    chartActivosTipo.setOption({
        tooltip: { trigger: "item" },
        series: [
            {
                type: "pie",
                radius: "60%",
                center: ["50%", "55%"],
                data: activosTipoData
            }
        ]
    });
}

// ============================
// 2. Activos por Estado (interactivo por tipo)
// ============================
var chartActivosEstado = initChart("chart-activos-estado");
function setActivosEstadoChart(tipo) {
    if (!chartActivosEstado) return;
    var data = activosEstadoTipoData?.[tipo] || [];
    chartActivosEstado.setOption({
        tooltip: { trigger: "item" },
        series: [
            {
                type: "pie",
                radius: "60%",
                center: ["50%", "55%"],
                data: data.map(e => ({ name: e.estado, value: e.cantidad }))
            }
        ]
    });
}
if (chartActivosEstado) {
    setActivosEstadoChart("Laptop");
    document.querySelectorAll(".btn-activos-estado").forEach(btn => {
        btn.addEventListener("click", () => {
            setActivosEstadoChart(btn.dataset.tipo);
            document.querySelectorAll(".btn-activos-estado").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
        });
    });
}

// ============================
// 3. Periféricos por Tipo y Estado (interactivo)
// ============================
var chartPerifericosTipo = initChart("chart-perifericos-tipo");

function setPerifericosTipoChart(estado) {
    if (!chartPerifericosTipo) return;
    // Usa la clave tal cual, sin .toLowerCase()
    var data = perifericosTipoEstadoData?.[estado] || [];
    chartPerifericosTipo.setOption({
        tooltip: { trigger: "item" },
        series: [
            {
                type: "pie",
                radius: "60%",
                center: ["50%", "55%"],
                // Usa el formato correcto: { name: tipo, value: cantidad }
                data: Array.isArray(data) ? data.map(e => ({ name: e.tipo, value: e.cantidad })) : []
            }
        ]
    });
}

if (chartPerifericosTipo) {
    setPerifericosTipoChart("Disponible");
    document.querySelectorAll(".btn-perifericos-tipo").forEach(btn => {
        btn.addEventListener("click", () => {
            setPerifericosTipoChart(btn.dataset.estado);
            document.querySelectorAll(".btn-perifericos-tipo").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
        });
    });
}
// ============================
// 4. Activos en Almacén por Localidad
// ============================
var chartActivosAlmacenLocalidad = initChart("chart-activos-almacen-localidad");
if (chartActivosAlmacenLocalidad) {
    chartActivosAlmacenLocalidad.setOption({
        tooltip: { trigger: "item" },
        series: [
            {
                type: "pie",
                radius: "60%",
                center: ["50%", "55%"],
                data: activosAlmacenLocalidadData.map(e => ({
                    name: e.localidad,
                    value: e.cantidad
                }))
            }
        ]
    });
}

// ============================
// 5. Activos por Marca y Tipo
// ============================
var chartActivosMarcaTipo = initChart("chart-activos-marca-tipo");
function setActivosMarcaTipoChart(tipo) {
    if (!chartActivosMarcaTipo) return;
    var data = activosMarcaTipoData?.[tipo] || [];
    chartActivosMarcaTipo.setOption({
        tooltip: { trigger: "axis" },
        xAxis: { type: "category", data: data.map(e => e.marca) },
        yAxis: { type: "value", minInterval: 1 },
        series: [{ type: "bar", barWidth: 40, data: data.map(e => e.cantidad) }]
    });
}
if (chartActivosMarcaTipo) {
    setActivosMarcaTipoChart("Laptop");
    document.querySelectorAll(".btn-marca-tipo").forEach(btn => {
        btn.addEventListener("click", () => {
            setActivosMarcaTipoChart(btn.dataset.tipo);
            document.querySelectorAll(".btn-marca-tipo").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
        });
    });
}

// ============================
// 6. Activos por Área y Tipo
// ============================
var chartActivosPorArea = initChart("chart-activos-por-area");
function setActivosPorAreaChart(tipo) {
    if (!chartActivosPorArea) return;
    var data = activosPorAreaTipoData?.[tipo] || [];
    chartActivosPorArea.setOption({
        tooltip: { trigger: "axis" },
        xAxis: { type: "category", data: data.map(e => e.area) },
        yAxis: { type: "value", minInterval: 1 },
        series: [{ type: "bar", barWidth: 40, data: data.map(e => e.cantidad) }]
    });
}
if (chartActivosPorArea) {
    setActivosPorAreaChart("Laptop");
    document.querySelectorAll(".btn-area-tipo").forEach(btn => {
        btn.addEventListener("click", () => {
            setActivosPorAreaChart(btn.dataset.tipo);
            document.querySelectorAll(".btn-area-tipo").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
        });
    });
}

// ============================
// 7. Costo y Cantidad de Reparaciones
// ============================
var chartCostoReparacion = initChart("chart-costo-reparacion");
function setCostoReparacionChart(costos, cantidades, mes, anio) {
    if (!chartCostoReparacion) return;
    chartCostoReparacion.setOption({
        tooltip: { trigger: "axis" },
        legend: { data: ["Costo", "Cantidad"] },
        xAxis: { type: "category", data: ["Laptop", "PC", "Servidor"] },
        yAxis: [{ type: "value", name: "Dólares" }, { type: "value", name: "Cantidad", minInterval: 1 }],
        series: [
            { name: "Costo", type: "bar", data: [costos.Laptop, costos.PC, costos.Servidor], yAxisIndex: 0 },
            { name: "Cantidad", type: "bar", data: [cantidades.Laptop, cantidades.PC, cantidades.Servidor], yAxisIndex: 1 }
        ]
    });
}
if (chartCostoReparacion && typeof selectedCostoMes !== "undefined") {
    setCostoReparacionChart(costosReparacionTipoData, cantReparacionTipoData, selectedCostoMes, selectedCostoAnio);
    document.getElementById("select-costo-mes")?.addEventListener("change", actualizarCostoReparacionAjax);
    document.getElementById("select-costo-anio")?.addEventListener("change", actualizarCostoReparacionAjax);
}
function actualizarCostoReparacionAjax() {
    var mes = +document.getElementById("select-costo-mes").value;
    var anio = +document.getElementById("select-costo-anio").value;
    fetch(`../../php/ajax/costo_reparacion_tipo.php?mes=${mes}&anio=${anio}`)
        .then(res => res.json())
        .then(data => setCostoReparacionChart(data.costos, data.cantidades, mes, anio));
}

// ============================
// 8. Costo y Cantidad de Cambios de HW
// ============================
var chartCostoCambioHW = initChart("chart-costo-cambiohw");
function setCostoCambioHWChart(costos, cantidades, mes, anio) {
    if (!chartCostoCambioHW) return;
    chartCostoCambioHW.setOption({
        tooltip: { trigger: "axis" },
        legend: { data: ["Costo", "Cantidad"] },
        xAxis: { type: "category", data: ["Laptop", "PC", "Servidor"] },
        yAxis: [{ type: "value", name: "Dólares" }, { type: "value", name: "Cantidad", minInterval: 1 }],
        series: [
            { name: "Costo", type: "bar", data: [costos.Laptop, costos.PC, costos.Servidor], yAxisIndex: 0 },
            { name: "Cantidad", type: "bar", data: [cantidades.Laptop, cantidades.PC, cantidades.Servidor], yAxisIndex: 1 }
        ]
    });
}
if (chartCostoCambioHW && typeof selectedCambioHWMes !== "undefined") {
    setCostoCambioHWChart(costosCambioHWTipoData, cantCambioHWTipoData, selectedCambioHWMes, selectedCambioHWAnio);
    document.getElementById("select-cambiohw-mes")?.addEventListener("change", actualizarCostoCambioHWAjax);
    document.getElementById("select-cambiohw-anio")?.addEventListener("change", actualizarCostoCambioHWAjax);
}
function actualizarCostoCambioHWAjax() {
    var mes = +document.getElementById("select-cambiohw-mes").value;
    var anio = +document.getElementById("select-cambiohw-anio").value;
    fetch(`../../php/ajax/costo_cambiohw_tipo.php?mes=${mes}&anio=${anio}`)
        .then(res => res.json())
        .then(data => setCostoCambioHWChart(data.costos, data.cantidades, mes, anio));
}

// ============================
// 9. Estado de Periféricos (doughnut)
// ============================
var chartEstadoPerifericos = initChart("chart-estado-perifericos");
function setEstadoPerifericosChart(data) {
    if (!chartEstadoPerifericos) return;
    var total = data.reduce((sum, i) => sum + i.value, 0);
    chartEstadoPerifericos.setOption({
        tooltip: {
            trigger: "item",
            formatter: p => `${p.name}<br>Cantidad: ${p.value}<br>Porcentaje: ${((p.value / total) * 100).toFixed(1)}%`
        },
        legend: { orient: "vertical", right: 10 },
        series: [
            {
                type: "pie",
                radius: ["40%", "70%"],
                data,
                label: {
                    formatter: p => `${p.name}\n${p.value} (${((p.value / total) * 100).toFixed(1)}%)`
                }
            }
        ]
    });
}
if (chartEstadoPerifericos && typeof perifericosEstadoData !== "undefined") {
    setEstadoPerifericosChart(perifericosEstadoData);
}

// ============================
// 10. Tendencia de Compra de Activos
// ============================
var chartTendenciaCompra = initChart("chart-tendencia-compra");
function setTendenciaCompraChart(data, mes, anio) {
    if (!chartTendenciaCompra) return;
    var tipos = ["Laptop", "PC", "Servidor"];
    var precios = tipos.map(tipo => {
        var items = data[tipo] || [];
        return {
            tipo,
            suma: items.reduce((acc, e) => acc + (e.precio || 0), 0),
            cantidad: items.length
        };
    });
    chartTendenciaCompra.setOption({
        tooltip: {
            trigger: "axis",
            formatter: p => {
                let info = "";
                p.forEach(x => {
                    info += `${x.name}: $${x.value.toFixed(2)}<br>Cantidad: ${precios[x.dataIndex].cantidad}<br>`;
                });
                return info;
            }
        },
        xAxis: { type: "category", data: tipos },
        yAxis: { type: "value", name: "Total Precio ($)" },
        series: [
            {
                type: "bar",
                data: precios.map(p => p.suma),
                label: {
                    show: true,
                    position: "top",
                    formatter: p => (p.value === 0 ? "" : `$${p.value.toFixed(2)}\n(${precios[p.dataIndex].cantidad})`)
                }
            }
        ]
    });
}
if (chartTendenciaCompra && typeof selectedTendenciaMes !== "undefined") {
    setTendenciaCompraChart(tendenciaCompraActivosData, selectedTendenciaMes, selectedTendenciaAnio);
    document.getElementById("select-tendencia-mes")?.addEventListener("change", actualizarTendenciaCompraAjax);
    document.getElementById("select-tendencia-anio")?.addEventListener("change", actualizarTendenciaCompraAjax);
}
function actualizarTendenciaCompraAjax() {
    var mes = +document.getElementById("select-tendencia-mes").value;
    var anio = +document.getElementById("select-tendencia-anio").value;
    fetch(`../../php/ajax/tendencia_compra_activos.php?mes=${mes}&anio=${anio}`)
        .then(res => res.json())
        .then(data => setTendenciaCompraChart(data, mes, anio));
}
