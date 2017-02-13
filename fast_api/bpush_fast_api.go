package main

import (
	"fmt"
	"io/ioutil"
	"net/http"

	"github.com/labstack/echo"
	"gopkg.in/redis.v5"
	"gopkg.in/yaml.v2"
)

const NotificationKey = "Notification/IncreaseReceivedCountBuffer/"
const NotificationSetKey = "Notification/IncreaseBufferSet"

type (
	StatusOnlyResponse struct {
		Status string `json:"status"`
	}
)

func main() {
	buf, err := ioutil.ReadFile("config.yml")
	if err != nil {
		panic(err)
	}
	config := make(map[interface{}]interface{})
	err = yaml.Unmarshal(buf, &config)
	redisConfig := config["Redis"].(map[interface{}]interface{})
	if err != nil {
		panic(err)
	}

	e := echo.New()

	client := redis.NewClient(&redis.Options{
		Addr:     redisConfig["Addr"].(string),
		Password: redisConfig["Password"].(string),
		DB:       redisConfig["DB"].(int),
		PoolSize: redisConfig["PoolSize"].(int),
	})
	keyPrefix := redisConfig["KeyPrefix"].(string)

	e.GET("/count_receive", func(c echo.Context) error {
		app_key := c.QueryParam("app_key")
		nid := c.QueryParam("nid")

		if len(app_key) == 0 || len(nid) == 0 {
			fmt.Printf("app_key or nid is empty.\n")
			return c.JSON(http.StatusOK, StatusOnlyResponse{Status: "failed"})
		}

		key := keyPrefix + NotificationKey + nid
		value, err := client.Incr(key).Result()
		if err != nil {
			fmt.Printf("%d, %s", value, err)
			return c.JSON(http.StatusOK, StatusOnlyResponse{Status: "failed"})
		}

		key = keyPrefix + NotificationSetKey
		value, err = client.SAdd(key, nid).Result()
		if err != nil {
			fmt.Printf("%d, %s", value, err)
			return c.JSON(http.StatusOK, StatusOnlyResponse{Status: "failed"})
		}

		c.Response().Header().Set(echo.HeaderAccessControlAllowOrigin, "*")
		return c.JSON(http.StatusOK, StatusOnlyResponse{Status: "success"})
	})

	e.Logger.Fatal(e.Start(config["Bind"].(string)))
}
