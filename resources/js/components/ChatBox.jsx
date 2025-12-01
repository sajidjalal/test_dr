import React, { useEffect, useRef, useState } from "react";
import Message from "./Message.jsx";
import MessageInput from "./MessageInput.jsx";
import axios from "axios";
import echo from "../echo.js"

const ChatBox = ({ rootUrl, chatId, onNewMessageCount, onNewLastChat, user, messageCounts }) => {
  const [messages, setMessages] = useState([]);
  const scroll = useRef();
  const webSocketChannel = `user.${user.id}.chat.${chatId}`;
  const readWebSocketChannel = `chat.${chatId}`;

  const scrollToBottom = () => {
    scroll.current?.scrollIntoView({ behavior: "smooth" });
  };

  const getMessages = async () => {

    if (!chatId) return;
      try {
        const res = await axios.get(`${rootUrl}/chats/${chatId}/messages`);
        setMessages(res.data.messages);
        onNewMessageCount(chatId, res.data.unReadCount);
        setTimeout(scrollToBottom, 0);
      } catch (err) {
        console.error(err);
      }
  };

  const connectWebSocket = () => {
    echo.private(webSocketChannel).listen("GotMessage", async (e) => {
      await getMessages();
      onNewLastChat(chatId, e.message['text']);
    });
  };

  const readMessage = async () => {
    if(messageCounts[chatId] != 0){
      const res = await axios.post(`${rootUrl}/chats/${chatId}/read-messages`);
      onNewMessageCount(chatId, res.data.unReadCount);
    }
  };

  const readMessageWebSocket = () => {
    echo.channel(readWebSocketChannel).listen("ReadMessage", async (e) => {
      await getMessages();
    });
  };

  useEffect(() => {
    getMessages();
    connectWebSocket();
    readMessageWebSocket();

    return () => {
      echo.leave(webSocketChannel);
      echo.leave(readWebSocketChannel);
    };
  }, [chatId]);

  if (!chatId) return <div className="p-3">Select a chat to start messaging</div>;

  return (
    // <div className="chat-box d-flex flex-column" style={{ height: "100%" }}>
    <div className="chat-box d-flex flex-column" style={{ height: "100%" }} onClick={readMessage}>
      <div className="card-body flex-grow-1 overflow-auto" style={{ height: "500px" }}>
        {messages.map(message => (
          <Message key={message.id} rootUrl={rootUrl} user={user} message={message} chatId={chatId} setMessages={setMessages} onNewMessageCount={onNewMessageCount} />
        ))}
        <span ref={scroll}></span>
      </div>
      <div className="card-footer">
        <MessageInput rootUrl={rootUrl} chatId={chatId} onSent={getMessages} user={user} />
      </div>
    </div>
  );
};

export default ChatBox;
