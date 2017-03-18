package server

import (
	"github.com/labstack/echo"
	"fmt"
	"net/http"
)

type (
	StatusOnlyResponse struct {
		Status string `json:"status"`
	}
)

const NotificationKey = "Notification/IncreaseReceivedCountBuffer/"
const NotificationSetKey = "Notification/IncreaseBufferSet"

func (server *Server) handleCountReceive (c echo.Context) error {
	app_key := c.QueryParam("app_key")
	nid := c.QueryParam("nid")
	redis := server.redis

	if len(app_key) == 0 || len(nid) == 0 {
		fmt.Printf("app_key or nid is empty.\n")
		return c.JSON(http.StatusOK, StatusOnlyResponse{Status: "failed"})
	}

	keyPrefix := server.config.Redis.KeyPrefix
	key := keyPrefix + NotificationKey + nid
	value, err := redis.Incr(key).Result()
	if err != nil {
		fmt.Printf("%d, %s", value, err)
		return c.JSON(http.StatusOK, StatusOnlyResponse{Status: "failed"})
	}

	key = keyPrefix + NotificationSetKey
	value, err = redis.SAdd(key, nid).Result()
	if err != nil {
		fmt.Printf("%d, %s", value, err)
		return c.JSON(http.StatusOK, StatusOnlyResponse{Status: "failed"})
	}

	c.Response().Header().Set(echo.HeaderAccessControlAllowOrigin, "*")
	return c.JSON(http.StatusOK, StatusOnlyResponse{Status: "success"})
}
