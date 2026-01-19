-- Minimal tilemaker process script
function init_function()
end

function exit_function()
end

function node_function(node)
	local name = node:get_value("name")
	local place = node:get_value("place")
	if place ~= "" and name ~= "" then
		node:add_to_layer("places", { name = name, class = place })
	end
end

function way_function(way)
	local highway = way:get_value("highway")
	local building = way:get_value("building")
	local name = way:get_value("name")

	if highway ~= "" then
		way:add_to_layer("roads", { class = highway, name = name })
	elseif building ~= "" then
		way:add_to_layer("buildings", {})
	end
end
