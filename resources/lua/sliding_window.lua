-- Sliding-window rate limit over one or more limits, checked and counted atomically.
--
-- KEYS: per limit, the current window's counter then the previous window's.
-- ARGV: now (unix seconds, float), then per limit max_attempts and decay_seconds.
--
-- A limit's count is the current window plus the previous window weighted by
-- how much of it still overlaps the sliding window. The request is counted
-- against every limit only when all of them allow it.
--
-- Returns {0, failed limit index (1-based), retry after seconds} when rejected,
-- or {1, remaining for limit 1, remaining for limit 2, ...} when allowed.

local now = tonumber(ARGV[1])
local limits = (#ARGV - 1) / 2
local remaining = {}

for i = 1, limits do
  local max = tonumber(ARGV[2 * i])
  local decay = tonumber(ARGV[2 * i + 1])
  local current = tonumber(redis.call("get", KEYS[2 * i - 1]) or "0")
  local previous = tonumber(redis.call("get", KEYS[2 * i]) or "0")
  local elapsed = now % decay
  local weight = 1 - elapsed / decay
  local estimate = previous * weight + current

  if estimate + 1 > max then
    local retry_after = decay - elapsed
    if current + 1 <= max and previous > 0 then
      retry_after = (1 - (max - current - 1) / previous) * decay - elapsed
    end
    return { 0, i, math.max(1, math.ceil(retry_after)) }
  end

  remaining[i] = math.floor(max - estimate - 1)
end

for i = 1, limits do
  local key = KEYS[2 * i - 1]
  redis.call("incr", key)
  redis.call("expire", key, 2 * tonumber(ARGV[2 * i + 1]))
end

local result = { 1 }
for i = 1, limits do
  result[i + 1] = remaining[i]
end
return result
