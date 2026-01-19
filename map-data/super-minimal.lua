-- Tilemaker 2.0.0 process script
node_keys = { "highway", "building" }

function init_function()
end

function exit_function()
end

function node_function()
end

function way_function()
	local highway = Find("highway")
	local building = Find("building")

	if highway ~= "" then
		Layer("roads", false)
		Attribute("class", highway)
	elseif building ~= "" then
		Layer("buildings", true)
	end
end
